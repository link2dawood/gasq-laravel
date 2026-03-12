<?php

namespace App\Http\Controllers;

use App\Models\ContentSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminContentSectionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request): View
    {
        if (! Schema::hasTable('content_sections')) {
            abort(503, 'Page Content is not set up yet. Run: php artisan migrate');
        }

        $page = $request->input('page', ContentSection::PAGE_PAYSCALE);
        $sections = ContentSection::forPage($page)->orderBy('order')->orderBy('id')->get();

        return view('admin.content-sections.index', [
            'sections' => $sections,
            'currentPage' => $page,
            'pageOptions' => ContentSection::pageSlugOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        $page = $request->input('page', ContentSection::PAGE_PAYSCALE);
        $section = new ContentSection([
            'page_slug' => $page,
            'order' => ContentSection::forPage($page)->max('order') + 1,
            'is_active' => true,
        ]);

        return view('admin.content-sections.form', [
            'section' => $section,
            'isEdit' => false,
            'pageOptions' => ContentSection::pageSlugOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'page_slug' => ['required', 'string', 'in:' . implode(',', array_keys(ContentSection::pageSlugOptions()))],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        ContentSection::create($data);

        return redirect()->route('admin.content-sections.index', ['page' => $data['page_slug']])->with('success', 'Section added.');
    }

    public function edit(ContentSection $content_section): View
    {
        return view('admin.content-sections.form', [
            'section' => $content_section,
            'isEdit' => true,
            'pageOptions' => ContentSection::pageSlugOptions(),
        ]);
    }

    public function update(Request $request, ContentSection $content_section): RedirectResponse
    {
        $data = $request->validate([
            'page_slug' => ['required', 'string', 'in:' . implode(',', array_keys(ContentSection::pageSlugOptions()))],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $content_section->update($data);

        return redirect()->route('admin.content-sections.index', ['page' => $data['page_slug']])->with('success', 'Section updated.');
    }

    public function destroy(ContentSection $content_section): RedirectResponse
    {
        $page = $content_section->page_slug;
        $content_section->delete();

        return redirect()->route('admin.content-sections.index', ['page' => $page])->with('success', 'Section deleted.');
    }
}
