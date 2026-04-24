<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceRequest;
use Illuminate\Http\Request;

class AdminInvoiceRequestController extends Controller
{
    /**
     * List all invoice requests with filters.
     */
    public function index(Request $request)
    {
        $query = InvoiceRequest::with(['user:id,name,email,phone,cccd', 'payment:id,order_code,amount,status,product_type,course_id,ebook_id,webinar_id', 'payment.course:id,title', 'payment.ebook:id,title', 'payment.webinar:id,title']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('tax_code', 'like', "%{$search}%")
                  ->orWhere('invoice_email', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $invoiceRequests = $query->orderByDesc('created_at')->paginate(20);

        // Stats
        $stats = [
            'total' => InvoiceRequest::count(),
            'pending' => InvoiceRequest::where('status', 'pending')->count(),
            'processing' => InvoiceRequest::where('status', 'processing')->count(),
            'completed' => InvoiceRequest::where('status', 'completed')->count(),
            'rejected' => InvoiceRequest::where('status', 'rejected')->count(),
        ];

        return response()->json([
            'invoice_requests' => $invoiceRequests,
            'stats' => $stats,
        ]);
    }

    /**
     * Update invoice request status and admin note.
     */
    public function update(Request $request, $id)
    {
        $invoiceRequest = InvoiceRequest::findOrFail($id);

        $request->validate([
            'status' => 'sometimes|in:pending,processing,completed,rejected',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        if ($request->has('status')) {
            $invoiceRequest->status = $request->status;
        }
        if ($request->has('admin_note')) {
            $invoiceRequest->admin_note = $request->admin_note;
        }

        $invoiceRequest->save();

        return response()->json([
            'message' => 'Cập nhật thành công',
            'invoice_request' => $invoiceRequest->load(['user:id,name,email,phone,cccd', 'payment:id,order_code,amount,status,product_type,course_id,ebook_id,webinar_id', 'payment.course:id,title', 'payment.ebook:id,title', 'payment.webinar:id,title']),
        ]);
    }

    /**
     * Delete an invoice request.
     */
    public function destroy($id)
    {
        $invoiceRequest = InvoiceRequest::findOrFail($id);
        $invoiceRequest->delete();

        return response()->json(['message' => 'Đã xóa yêu cầu hóa đơn']);
    }
}
