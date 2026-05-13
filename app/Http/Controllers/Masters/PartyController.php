<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartyController extends Controller
{
    public function index(Request $request)
    {
        $type     = $request->get('type', 'ALL'); // ALL | C | S
        $brCode   = session('br_code');

        $query = Party::with('branch')->where('br_code', $brCode);

        if (in_array($type, ['C', 'S'])) {
            $query->where('party_type', $type);
        }

        $parties = $query->orderBy('party_name')->get();

        return view('masters.party.index', compact('parties', 'type'));
    }

    public function create(Request $request)
    {
        $branches     = Branch::orderBy('br_name')->get();
        $defaultType  = $request->get('type', 'C'); // pre-select Customer or Supplier
        return view('masters.party.form', [
            'party'       => null,
            'branches'    => $branches,
            'defaultType' => $defaultType,
            'states'      => $this->indianStates(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'br_code'      => 'required|integer|exists:branches,br_code',
            'party_type'   => 'required|in:C,S',
            'party_name'   => [
                'required', 'string', 'max:100',
                Rule::unique('parties')->where(fn($q) =>
                    $q->where('br_code', $request->br_code)
                      ->where('party_type', $request->party_type)
                ),
            ],
            'address'      => 'nullable|string|max:150',
            'place'        => 'required|string|max:50',
            'state'        => 'required|string|max:60',
            'phone'        => 'nullable|digits:10',
            'mobile'       => 'nullable|digits:10',
            'inout_state'  => 'required|in:0,1',
            'tin_grn_flag' => 'nullable|in:0,1',
            'tin_grn_no'   => [
                'nullable', 'string',
                function ($attr, $value, $fail) use ($request) {
                    if ($request->tin_grn_flag == 1) {
                        if (empty($value)) {
                            $fail('GST/TIN No. is required when GST Registered is checked.');
                        } elseif (strlen($value) !== 15) {
                            $fail('GST/TIN No. must be exactly 15 characters.');
                        }
                    }
                },
            ],
        ]);

        $validated['tin_grn_flag'] = $request->has('tin_grn_flag') ? 1 : 0;
        if ($validated['tin_grn_flag'] == 0) {
            $validated['tin_grn_no'] = null;
        }

        Party::create($validated);

        return redirect()->route('masters.party.index', ['type' => $validated['party_type']])
                         ->with('success', 'Party added successfully.');
    }

    public function edit($id)
    {
        $party    = Party::findOrFail($id);
        $branches = Branch::orderBy('br_name')->get();
        return view('masters.party.form', [
            'party'       => $party,
            'branches'    => $branches,
            'defaultType' => $party->party_type,
            'states'      => $this->indianStates(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $party = Party::findOrFail($id);

        $validated = $request->validate([
            'br_code'      => 'required|integer|exists:branches,br_code',
            'party_type'   => 'required|in:C,S',
            'party_name'   => [
                'required', 'string', 'max:100',
                Rule::unique('parties')->where(fn($q) =>
                    $q->where('br_code', $request->br_code)
                      ->where('party_type', $request->party_type)
                )->ignore($id, 'party_code'),
            ],
            'address'      => 'nullable|string|max:150',
            'place'        => 'required|string|max:50',
            'state'        => 'required|string|max:60',
            'phone'        => 'nullable|digits:10',
            'mobile'       => 'nullable|digits:10',
            'inout_state'  => 'required|in:0,1',
            'tin_grn_flag' => 'nullable|in:0,1',
            'tin_grn_no'   => [
                'nullable', 'string',
                function ($attr, $value, $fail) use ($request) {
                    if ($request->tin_grn_flag == 1) {
                        if (empty($value)) {
                            $fail('GST/TIN No. is required when GST Registered is checked.');
                        } elseif (strlen($value) !== 15) {
                            $fail('GST/TIN No. must be exactly 15 characters.');
                        }
                    }
                },
            ],
        ]);

        $validated['tin_grn_flag'] = $request->has('tin_grn_flag') ? 1 : 0;
        if ($validated['tin_grn_flag'] == 0) {
            $validated['tin_grn_no'] = null;
        }

        $party->update($validated);

        return redirect()->route('masters.party.index', ['type' => $party->party_type])
                         ->with('success', 'Party updated successfully.');
    }

    public function destroy($id)
    {
        $party = Party::findOrFail($id);

        // Check if party has any transactions
        $usedIn = [];

        if (\DB::table('purchase_hdr')->where('party_code', $id)->exists()) {
            $usedIn[] = 'Purchase';
        }
        if (\DB::table('sale_hdr')->where('party_code', $id)->exists()) {
            $usedIn[] = 'Sale';
        }
        if (\DB::table('order_hdr')->where('party_code', $id)->exists()) {
            $usedIn[] = 'Order';
        }
        if (\DB::table('sale_rtn_hdr')->where('party_code', $id)->exists()) {
            $usedIn[] = 'Sale Return';
        }
        if (\DB::table('purchase_rtn_hdr')->where('party_code', $id)->exists()) {
            $usedIn[] = 'Purchase Return';
        }

        if (!empty($usedIn)) {
            return redirect()->route('masters.party.index')
                             ->with('error', "Cannot delete '{$party->party_name}'. It is used in: " . implode(', ', $usedIn) . '.');
        }

        $type = $party->party_type;
        $party->delete();

        return redirect()->route('masters.party.index', ['type' => $type])
                         ->with('success', 'Party deleted successfully.');
    }

    private function indianStates(): array
    {
        return [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar',
            'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana',
            'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala',
            'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya',
            'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
            'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana',
            'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
            'Jammu & Kashmir', 'Ladakh', 'Delhi',
            'Puducherry', 'Chandigarh', 'Dadra & Nagar Haveli', 'Lakshadweep',
        ];
    }
}
