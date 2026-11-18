<?php

namespace App\Http\Controllers;

use App\Models\VendorEstimateAttachment;
use App\Models\VendorLeadEstimate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Supporting files and photos on a vendor estimate.
 *
 * Everything here is scoped to the signed-in vendor who owns the estimate.
 * Files are written to the private local disk and never served from public/,
 * so a leaked path cannot expose a buyer's site photographs.
 */
class VendorEstimateAttachmentController extends Controller
{
    public function store(Request $request, VendorLeadEstimate $estimate)
    {
        $this->owner($request, $estimate);
        $request->validate([
            'attachments' => ['required', 'array', 'min:1'],
            'attachments.*' => ['file', 'mimes:'.implode(',', VendorEstimateAttachment::ALLOWED_EXTENSIONS), 'max:'.VendorEstimateAttachment::MAX_KILOBYTES],
        ], [], ['attachments.*' => 'file']);

        $files = array_values(array_filter((array) $request->file('attachments', [])));
        $room = VendorEstimateAttachment::MAX_PER_ESTIMATE - $estimate->attachments()->count();
        if (count($files) > $room) {
            throw ValidationException::withMessages([
                'attachments' => 'This estimate holds at most '.VendorEstimateAttachment::MAX_PER_ESTIMATE.' files. Room for '.max(0, $room).' more.',
            ]);
        }

        foreach ($files as $file) {
            $path = $file->store('vendor-estimate-attachments/'.$estimate->id, 'local');
            if (! $path) {
                throw ValidationException::withMessages(['attachments' => 'The file could not be saved. Please try again.']);
            }
            $estimate->attachments()->create([
                'uploaded_by' => $request->user()->id,
                // Keep the vendor's own name for display, but never let it reach
                // the filesystem or a header unsanitised.
                'original_name' => Str::limit(trim((string) $file->getClientOriginalName()), 180, ''),
                'path' => $path,
                'mime_type' => (string) ($file->getClientMimeType() ?: 'application/octet-stream'),
                'size_bytes' => (int) Storage::disk('local')->size($path),
                'sha256' => (string) hash_file('sha256', Storage::disk('local')->path($path)),
            ]);
        }

        return back()->with('status', count($files) === 1 ? 'File attached to this estimate.' : count($files).' files attached to this estimate.');
    }

    public function download(Request $request, VendorLeadEstimate $estimate, VendorEstimateAttachment $attachment)
    {
        $this->owner($request, $estimate);
        abort_unless($attachment->vendor_lead_estimate_id === $estimate->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return response(Storage::disk('local')->get($attachment->path), 200, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $attachment->original_name),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, VendorLeadEstimate $estimate, VendorEstimateAttachment $attachment)
    {
        $this->owner($request, $estimate);
        abort_unless($attachment->vendor_lead_estimate_id === $estimate->id, 404);

        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return back()->with('status', 'File removed from this estimate.');
    }

    private function owner(Request $request, VendorLeadEstimate $estimate): void
    {
        abort_unless($request->user()->isVendor() && $estimate->vendor_id === $request->user()->id, 404);
    }
}
