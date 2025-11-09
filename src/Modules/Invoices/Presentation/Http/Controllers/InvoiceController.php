<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http\Controllers;

use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
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

        return response()->json([
            'id' => $invoice->id,
            'status' => $invoice->status,
            'customer_name' => $invoice->customer_name,
            'customer_email' => $invoice->customer_email,
            'product_lines' => $invoice->productLines->map(function ($line) {
                return [
                    'name' => $line->name,
                    'quantity' => $line->quantity,
                    'price' => $line->price,
                    'total_price' => $line->getTotalUnitPrice(),
                ];
            }),
            'total_price' => $invoice->getTotalPrice(),
        ]);
    }

    public function store(Request $request)
    {
        // Transform product lines from request to domain entities
        $productLinesData = $request->input('product_lines', []);
        $productLines = array_map(function ($lineData) {
            return new \Modules\Invoices\Domain\Entities\InvoiceProductLine(
                $lineData['name'] ?? '',
                $lineData['quantity'] ?? 0,
                $lineData['price'] ?? 0
            );
        }, $productLinesData);

        $invoice = $this->invoiceService->createInvoice(
            $request->input('customer_name'),
            $request->input('customer_email'),
            $productLines
        );

        return response()->json([
            'id' => $invoice->id,
            'status' => $invoice->status,
            'customer_name' => $invoice->customer_name,
            'customer_email' => $invoice->customer_email,
            'product_lines' => $invoice->productLines->map(function ($line) {
                return [
                    'name' => $line->name,
                    'quantity' => $line->quantity,
                    'price' => $line->price,
                    'total_price' => $line->getTotalUnitPrice(),
                ];
            }),
            'total_price' => $invoice->getTotalPrice(),
        ], 201);
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
