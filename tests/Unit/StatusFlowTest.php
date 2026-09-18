<?php

namespace Tests\Unit;

use App\Models\Quotation;
use App\Models\Rfq;
use PHPUnit\Framework\TestCase;

class StatusFlowTest extends TestCase
{
    public function test_rfq_can_move_from_pending_admin_to_pending_leader(): void
    {
        $rfq = new Rfq(['status' => Rfq::STATUS_PENDING_ADMIN]);

        $this->assertTrue($rfq->canTransitionTo(Rfq::STATUS_PENDING_LEADER));
        $this->assertFalse($rfq->canTransitionTo(Rfq::STATUS_GOAL));
    }

    public function test_rfq_can_move_from_pending_leader_to_approved_or_pending_admin(): void
    {
        $rfq = new Rfq(['status' => Rfq::STATUS_PENDING_LEADER]);

        $this->assertTrue($rfq->canTransitionTo(Rfq::STATUS_APPROVED));
        $this->assertTrue($rfq->canTransitionTo(Rfq::STATUS_PENDING_ADMIN));
        $this->assertFalse($rfq->canTransitionTo(Rfq::STATUS_GOAL));
    }

    public function test_quotation_can_move_from_sent_to_approved(): void
    {
        $quotation = new Quotation(['status' => Quotation::STATUS_SENT]);

        $this->assertTrue($quotation->canTransitionTo(Quotation::STATUS_APPROVED));
        $this->assertFalse($quotation->canTransitionTo(Quotation::STATUS_SENT));
    }

    public function test_quotation_cannot_skip_directly_to_approved_from_draft(): void
    {
        $quotation = new Quotation(['status' => Quotation::STATUS_DRAFT]);

        $this->assertFalse($quotation->canTransitionTo(Quotation::STATUS_APPROVED));
    }

    public function test_rfq_price_submission_is_allowed_only_from_pending_admin(): void
    {
        $rfq = new Rfq(['status' => Rfq::STATUS_PENDING_ADMIN]);
        $this->assertTrue($rfq->canBePriceSubmitted());

        $rfq->status = Rfq::STATUS_PENDING_LEADER;
        $this->assertFalse($rfq->canBePriceSubmitted());
    }

    public function test_rfq_next_action_label_matches_current_stage(): void
    {
        $rfq = new Rfq(['status' => Rfq::STATUS_PENDING_ADMIN]);
        $this->assertSame('Menunggu Admin Purchase mengisi harga', $rfq->getNextActionLabel());

        $rfq->status = Rfq::STATUS_PENDING_LEADER;
        $this->assertSame('Menunggu approval Leader', $rfq->getNextActionLabel());
    }

    public function test_rfq_workflow_progress_matches_stage(): void
    {
        $rfq = new Rfq(['status' => Rfq::STATUS_PENDING_ADMIN]);
        $this->assertSame(20, $rfq->getWorkflowProgressPercent());
        $this->assertSame('Tahap 1 - Persiapan harga', $rfq->getWorkflowStageLabel());

        $rfq->status = Rfq::STATUS_GOAL;
        $this->assertSame(100, $rfq->getWorkflowProgressPercent());
        $this->assertSame('Tahap 7 - GOAL selesai', $rfq->getWorkflowStageLabel());
    }
}
