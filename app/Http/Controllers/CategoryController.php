<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', 'in:income,expense'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $categories = $request->user()->categories()
            ->with('parent')
            ->withCount(['transactions', 'budgets', 'bills', 'children'])
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('type')->orderBy('name')->paginate(20)->withQueryString();

        return view('categories.index', compact('categories', 'filters'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Category::class);

        return view('categories.create', [
            'types' => Category::TYPES,
            'parents' => $request->user()->categories()->orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $request->user()->categories()->create($request->validated());

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dibuat.');
    }

    public function edit(Request $request, Category $category): View
    {
        Gate::authorize('update', $category);

        return view('categories.edit', [
            'category' => $category,
            'types' => Category::TYPES,
            'parents' => $request->user()->categories()->whereKeyNot($category->id)->orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        if ($category->isUsed()) {
            $category->update(['is_active' => false]);

            return redirect()->route('categories.index')
                ->with('warning', 'Kategori ini sudah digunakan pada data keuangan. Kategori telah dinonaktifkan agar histori tetap utuh.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus secara permanen.');
    }
}
