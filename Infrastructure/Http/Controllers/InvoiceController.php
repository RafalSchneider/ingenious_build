<?php

namespace Infrastructure\Http\Controllers;

use Application\Invoices\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InvoiceController extends Controller
{
    private InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function show($id)
    {
        $invoice = $this->invoiceService->getInvoice($id);
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }
        return response()->json($invoice);
    }

    public function store(Request $request)
    {
        $invoice = $this->invoiceService->createInvoice(
            $request->input('customer_name'),
            $request->input('customer_email'),
            $request->input('product_lines', [])
        );
        return response()->json($invoice, 201);
    }

    public function send($id)
    {
        $result = $this->invoiceService->sendInvoice($id);
        if ($result) {
            return response()->json(['message' => 'Invoice sent']);
        }
        return response()->json(['error' => 'Invoice cannot be sent'], 400);
    }
}
