<?php

namespace App\View\Components;

use App\Services\MonthlyPoReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class PoExportModal extends Component
{
    public bool $canExport;
    public bool $canFilterSales;
    public int $defaultMonth;
    public int $defaultYear;

    /** @var array<int, string> */
    public array $months;

    /** @var array<int, int> */
    public array $years;

    /** @var Collection<int, \App\Models\User> */
    public Collection $salesList;

    public string $buttonLabel;
    public string $modalId;

    public function __construct(
        MonthlyPoReportService $reportService,
        string $buttonLabel = 'Export PO Excel',
    ) {
        $user = auth()->user();

        $this->canExport = $user !== null && ($user->isSales() || $user->isAdminOrAbove());
        $this->canFilterSales = $user !== null && $user->isAdminOrAbove();
        $this->defaultMonth = (int) now()->month;
        $this->defaultYear = (int) now()->year;
        $this->months = MonthlyPoReportService::MONTHS_ID;
        $this->years = $this->canExport ? $reportService->getAvailableYears() : [$this->defaultYear];
        $this->salesList = $this->canFilterSales ? $reportService->getActiveSalesList() : collect();
        $this->buttonLabel = $buttonLabel;
        $this->modalId = 'po-export-modal-' . uniqid();
    }

    public function render(): View|string
    {
        if (!$this->canExport) {
            return '';
        }

        return view('components.po-export-modal');
    }
}
