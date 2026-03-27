@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Create Purchase</h1>
            <p class="text-sm text-gray-500">Enter supplier bill items manually and keep the ledger clean.</p>
        </div>

        <form action="{{ route('purchases.store') }}" method="POST" class="space-y-6" x-data="billForm()">
            @csrf
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <label for="party_id" class="block text-sm font-medium text-gray-700">Party</label>
                <select id="party_id" name="party_id" class="select2 mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                    <option value="">Select a party</option>
                    @foreach ($parties as $party)
                        <option value="{{ $party->id }}" @selected(old('party_id') === $party->id)>{{ $party->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Items</h2>
                    <button type="button" @click="addItem()" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">+ Add Item</button>
                </div>

                <div class="space-y-3">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid gap-3 rounded-lg border border-gray-200 p-4 md:grid-cols-12">
                            <div class="md:col-span-5">
                                <label class="block text-xs font-medium uppercase tracking-wide text-gray-500">Particular</label>
                                <input x-model="item.particular" :name="`items[${index}][particular]`" type="text" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium uppercase tracking-wide text-gray-500">Qty</label>
                                <input x-model.number="item.qty" :name="`items[${index}][qty]`" type="number" step="0.01" min="0.01" @input="recalc(index)" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-right font-mono outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium uppercase tracking-wide text-gray-500">Price</label>
                                <input x-model.number="item.price" :name="`items[${index}][price]`" type="number" step="0.01" min="0" @input="recalc(index)" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-right font-mono outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium uppercase tracking-wide text-gray-500">Total</label>
                                <div class="mt-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-right font-mono text-indigo-700" x-text="currency(item.total)"></div>
                            </div>
                            <div class="flex items-end justify-end md:col-span-1">
                                <button type="button" @click="removeItem(index)" class="rounded-lg px-3 py-2 text-sm text-red-500 hover:bg-red-50 hover:text-red-700">Remove</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-6 flex items-center justify-end border-t border-gray-200 pt-4">
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Grand Total</p>
                        <p class="font-mono text-2xl font-semibold text-indigo-700" x-text="currency(grandTotal())"></p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('purchases.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save Purchase</button>
            </div>
        </form>
    </div>

    <script>
        function billForm() {
            return {
                items: [
                    { particular: '', qty: 1, price: 0, total: 0 }
                ],
                addItem() {
                    this.items.push({ particular: '', qty: 1, price: 0, total: 0 });
                },
                removeItem(index) {
                    if (this.items.length === 1) {
                        this.items[0] = { particular: '', qty: 1, price: 0, total: 0 };
                        return;
                    }
                    this.items.splice(index, 1);
                },
                recalc(index) {
                    const qty = Number(this.items[index].qty || 0);
                    const price = Number(this.items[index].price || 0);
                    this.items[index].total = qty * price;
                },
                grandTotal() {
                    return this.items.reduce((sum, item) => sum + Number(item.total || 0), 0);
                },
                currency(value) {
                    return Number(value || 0).toFixed(2);
                }
            }
        }
    </script>
@endsection



