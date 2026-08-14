<?php

declare(strict_types=1);

use App\Domain\CrmCatalog;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CrmCatalogTest extends CIUnitTestCase
{
    public function testOpportunityStatusesArePartitionedIntoOpenAndClosedSets(): void
    {
        $allStatuses = array_keys(CrmCatalog::OPPORTUNITY_STATUSES);
        $partition = array_merge(
            CrmCatalog::OPEN_OPPORTUNITY_STATUSES,
            CrmCatalog::CLOSED_OPPORTUNITY_STATUSES,
        );

        sort($allStatuses);
        sort($partition);

        $this->assertSame($allStatuses, $partition);
        $this->assertSame(
            [],
            array_intersect(
                CrmCatalog::OPEN_OPPORTUNITY_STATUSES,
                CrmCatalog::CLOSED_OPPORTUNITY_STATUSES,
            ),
        );
    }

    public function testProvisionalCatalogsExposeTheDemoVocabulary(): void
    {
        $this->assertSame('Prospecto', CrmCatalog::LIFECYCLE_STAGES['prospect']);
        $this->assertSame('Cliente', CrmCatalog::LIFECYCLE_STAGES['client']);
        $this->assertSame('Instagram', CrmCatalog::ACQUISITION_CHANNELS['instagram']);
        $this->assertSame('WhatsApp', CrmCatalog::ACQUISITION_CHANNELS['whatsapp']);
        $this->assertSame('Recomendación', CrmCatalog::ACQUISITION_CHANNELS['referral']);
        $this->assertSame('Propuesta enviada', CrmCatalog::OPPORTUNITY_STATUSES['proposal_sent']);
    }
}
