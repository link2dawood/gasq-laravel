<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCouponController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(): View
    {
        $coupons = Coupon::query()
            ->withCount('redemptions')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        $coupon = new Coupon([
            'is_active' => true,
        ]);

        return view('admin.coupons.form', ['coupon' => $coupon, 'isEdit' => false]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCoupon($request);
        Coupon::create($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon): View
    {
        $coupon->loadCount('redemptions');

        return view('admin.coupons.form', ['coupon' => $coupon, 'isEdit' => true]);
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $this->validateCoupon($request, $coupon);
        $coupon->update($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCoupon(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:64',
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],
            'credits_amount' => ['required', 'integer', 'min:1'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
