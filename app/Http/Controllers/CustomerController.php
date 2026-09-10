<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordPaymentRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CustomerController extends Controller
{
    public function __construct(private BillingService $billingService) {}


    public function index(Request $request)
    {
        try {
            $filters = $request->validate([
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|in:all,due,clear,credit',
            ]);

            $query = Customer::query()
                ->withCount('orders')
                ->withSum('orders as total_billed', 'grand_total')
                ->withSum('orders as total_counter_paid', 'amount_given')
                ->withSum(['payments as total_manual_paid' => function ($q) {
                    $q->where('type', 'due_payment');
                }], 'amount');

            if (!empty($filters['search'])) {
                $s = $filters['search'];
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%");
                });
            }

            $customers = $query->orderBy('name')->paginate(15)->withQueryString();

            $customers->getCollection()->transform(function ($c) {
                $billed = (float) ($c->total_billed ?? 0);
                $counterPaid = (float) ($c->total_counter_paid ?? 0);
                $manualPaid = (float) ($c->total_manual_paid ?? 0);
                $c->computed_balance = round($billed - $counterPaid - $manualPaid, 2);
                return $c;
            });

            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $filtered = $customers->getCollection()->filter(function ($c) use ($filters) {
                    return match ($filters['status']) {
                        'due'    => $c->computed_balance > 0,
                        'credit' => $c->computed_balance < 0,
                        'clear'  => $c->computed_balance == 0,
                        default  => true,
                    };
                })->values();

                $customers->setCollection($filtered);
            }

            $allCustomers = Customer::withSum('orders as total_billed', 'grand_total')
                ->withSum('orders as total_counter_paid', 'amount_given')
                ->withSum(['payments as total_manual_paid' => function ($q) {
                    $q->where('type', 'due_payment');
                }], 'amount')
                ->get();

            $stats = [
                'total_customers' => $allCustomers->count(),
                'with_due' => 0,
                'total_due' => 0,
                'with_credit' => 0,
                'total_credit' => 0,
            ];

            foreach ($allCustomers as $c) {
                $b = round(
                    (float) ($c->total_billed ?? 0)
                        - (float) ($c->total_counter_paid ?? 0)
                        - (float) ($c->total_manual_paid ?? 0),
                    2
                );
                if ($b > 0) {
                    $stats['with_due']++;
                    $stats['total_due'] += $b;
                } elseif ($b < 0) {
                    $stats['with_credit']++;
                    $stats['total_credit'] += abs($b);
                }
            }

            return view('customers.index', compact('customers', 'stats'));
        } catch (Throwable $e) {
            Log::error('Failed to load customers list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('billing.index')
                ->with('error', 'Unable to load customers.');
        }
    }


    public function store(StoreCustomerRequest $request)
    {
        try {
            $customer = Customer::create($request->validated());

            return redirect()
                ->route('customers.index')
                ->with('success', "Customer \"{$customer->name}\" created successfully.");
        } catch (Throwable $e) {
            Log::error('Failed to create customer', [
                'data' => $request->validated(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Failed to create customer. Please try again.')
                ->withInput();
        }
    }


    public function editData(Customer $customer)
    {
        try {
            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'created_at' => $customer->created_at->format('d M Y, H:i'),
                    'updated_at' => $customer->updated_at->diffForHumans(),
                    'orders_count' => $customer->orders()->count(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to load customer edit data', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load customer.',
            ], 500);
        }
    }


    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        try {
            $customer->update($request->validated());

            return redirect()
                ->route('customers.index')
                ->with('success', "Customer \"{$customer->name}\" updated successfully.");
        } catch (Throwable $e) {
            Log::error('Failed to update customer', [
                'customer_id' => $customer->id,
                'data' => $request->validated(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Failed to update customer. Please try again.')
                ->withInput();
        }
    }


    public function destroy(Customer $customer)
    {
        try {
            if ($customer->orders()->exists()) {
                return redirect()
                    ->route('customers.index')
                    ->with('error', "Cannot delete \"{$customer->name}\" — they have existing orders.");
            }

            if ($customer->payments()->exists()) {
                return redirect()
                    ->route('customers.index')
                    ->with('error', "Cannot delete \"{$customer->name}\" — they have payment history.");
            }

            $name = $customer->name;
            $customer->delete();

            return redirect()
                ->route('customers.index')
                ->with('success', "Customer \"{$name}\" deleted successfully.");
        } catch (Throwable $e) {
            Log::error('Failed to delete customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('customers.index')
                ->with('error', 'Failed to delete customer.');
        }
    }


    public function recordPayment(RecordPaymentRequest $request, Customer $customer)
    {
        try {
            $this->billingService->recordPayment($customer, $request->validated());

            $fresh = $customer->fresh();
            $message = $fresh->balance > 0
                ? "Payment recorded. Remaining due: ₹" . number_format($fresh->balance, 2)
                : ($fresh->balance < 0
                    ? "Payment recorded. Customer now has ₹" . number_format(abs($fresh->balance), 2) . " credit."
                    : "Payment recorded. Customer is fully settled.");

            return redirect()
                ->route('customers.index')
                ->with('success', $message);
        } catch (Throwable $e) {
            Log::error('Failed to record payment', [
                'customer_id' => $customer->id,
                'data' => $request->validated(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Failed to record payment. Please try again.')
                ->withInput();
        }
    }


    public function show(Customer $customer)
    {
        try {
            $customer->load([
                'orders.items.product',
                'payments' => fn($q) => $q->latest(),
            ]);

            $billed = (float) $customer->orders->sum('grand_total');
            $counterPaid = (float) $customer->orders->sum('amount_given');
            $manualPaid = (float) $customer->payments->where('type', 'due_payment')->sum('amount');
            $balance = round($billed - $counterPaid - $manualPaid, 2);

            return view('customers.show', compact('customer', 'billed', 'counterPaid', 'manualPaid', 'balance'));
        } catch (Throwable $e) {
            Log::error('Failed to load customer detail', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('customers.index')
                ->with('error', 'Unable to load customer.');
        }
    }
}
