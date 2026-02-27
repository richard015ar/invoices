<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCatalogItemRequest;
use App\Http\Requests\UpdateCatalogItemRequest;
use App\Models\CatalogItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CatalogItemController extends Controller
{
    public function index(): View
    {
        return view('catalog-items.index', [
            'items' => CatalogItem::query()->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('catalog-items.create', [
            'item' => new CatalogItem(['is_active' => true]),
        ]);
    }

    public function store(StoreCatalogItemRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        CatalogItem::query()->create([
            ...$validated,
            'default_tax_rate' => $validated['default_tax_rate'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('catalog-items.index')->with('success', 'Item guardado.');
    }

    public function edit(CatalogItem $catalogItem): View
    {
        return view('catalog-items.edit', [
            'item' => $catalogItem,
        ]);
    }

    public function update(UpdateCatalogItemRequest $request, CatalogItem $catalogItem): RedirectResponse
    {
        $validated = $request->validated();

        $catalogItem->update([
            ...$validated,
            'default_tax_rate' => $validated['default_tax_rate'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('catalog-items.index')->with('success', 'Item actualizado.');
    }

    public function destroy(CatalogItem $catalogItem): RedirectResponse
    {
        $catalogItem->delete();

        return redirect()->route('catalog-items.index')->with('success', 'Item eliminado.');
    }
}
