<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Model\Sync\IntegrationInsightsFactory;
use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

class Cron
{
    /**
     * @var Email\TemplateFactory
     */
    private $templateFactory;

    /**
     * @var Apiconnector\ContactFactory
     */
    private $contactFactory;

    /**
     * @var Sync\AutomationFactory
     */
    private $automationFactory;

    /**
     * @var Sync\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var Sync\CatalogFactory
     */
    private $catalogFactory;

    /**
     * @var Newsletter\SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var Customer\GuestFactory
     */
    private $guestFactory;

    /**
     * @var Sales\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var Sync\OrderFactory
     */
    private $syncOrderFactory;

    /**
     * @var Sync\CampaignFactory
     */
    private $campaignFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    private $fileHelper;

    /**
     * @var ResourceModel\Importer
     */
    private $importerResource;

    /**
     * @var ResourceModel\Cron\CollectionFactory
     */
    private $cronCollection;

    /**
     * @var Cron\CronSub
     */
    private $cronHelper;

    /**
     * @var IntegrationInsightsFactory
     */
    private $integrationInsights;

    /**
     * @var AbandonedCart\ProgramEnrolment\Enroller
     */
    private $abandonedCartProgramEnroller;

    /**
     * @var MonitorFactory
     */
    private $monitor;

    /**
     * Cron constructor.
     *
     * @param Sync\CampaignFactory $campaignFactory
     * @param Sync\OrderFactory $syncOrderFactory
     * @param Sales\QuoteFactory $quoteFactory
     * @param Customer\GuestFactory $guestFactory
     * @param Newsletter\SubscriberFactory $subscriberFactory
     * @param Sync\CatalogFactory $catalogFactory
     * @param Sync\ImporterFactory $importerFactory
     * @param Sync\AutomationFactory $automationFactory
     * @param Apiconnector\ContactFactory $contact
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param ResourceModel\Importer $importerResource
     * @param Email\TemplateFactory $templateFactory
     * @param ResourceModel\Cron\CollectionFactory $cronCollection
     * @param Cron\CronSubFactory $cronSubFactory
     * @param AbandonedCart\ProgramEnrolment\Enroller $abandonedCartProgramEnroller
     * @param IntegrationInsightsFactory $integrationInsightsFactory
     * @param MonitorFactory $monitorFactory
     */
    public function __construct(
        Sync\CampaignFactory $campaignFactory,
        Sync\OrderFactory $syncOrderFactory,
        Sales\QuoteFactory $quoteFactory,
        Customer\GuestFactory $guestFactory,
        Newsletter\SubscriberFactory $subscriberFactory,
        Sync\CatalogFactory $catalogFactory,
        Sync\ImporterFactory $importerFactory,
        Sync\AutomationFactory $automationFactory,
        Apiconnector\ContactFactory $contact,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        ResourceModel\Importer $importerResource,
        Email\TemplateFactory $templateFactory,
        ResourceModel\Cron\CollectionFactory $cronCollection,
        Cron\CronSubFactory $cronSubFactory,
        AbandonedCart\ProgramEnrolment\Enroller $abandonedCartProgramEnroller,
        IntegrationInsightsFactory $integrationInsightsFactory,
        MonitorFactory $monitorFactory
    ) {
        $this->campaignFactory   = $campaignFactory;
        $this->syncOrderFactory  = $syncOrderFactory;
        $this->quoteFactory      = $quoteFactory;
        $this->guestFactory      = $guestFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->catalogFactory    = $catalogFactory;
        $this->importerFactory   = $importerFactory;
        $this->automationFactory = $automationFactory;
        $this->contactFactory    = $contact;
        $this->fileHelper        = $fileHelper;
        $this->importerResource  = $importerResource;
        $this->cronCollection    = $cronCollection;
        $this->templateFactory   = $templateFactory;
        $this->cronHelper        = $cronSubFactory->create();
        $this->abandonedCartProgramEnroller = $abandonedCartProgramEnroller;
        $this->integrationInsights = $integrationInsightsFactory;
        $this->monitor = $monitorFactory;
    }

    /**
     * @return array|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function contactSync()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_customer_subscriber_guest_sync')) {
            return;
        }

        //run the sync for contacts
        $result = $this->contactFactory->create()
            ->sync();
        //run subscribers and guests sync
        $subscriberResult = $this->subscribersAndGuestSync();

        $result['message'] .= ' - ' . $subscriberResult['message'];

        return $result;
    }

    /**
     * CRON FOR SUBSCRIBERS AND GUEST CONTACTS.
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function subscribersAndGuestSync()
    {
        //sync subscribers
        $subscriberModel = $this->subscriberFactory->create();
        $result = $subscriberModel->runExport();

        //un-subscribe suppressed contacts
        $subscriberModel->unsubscribe();

        //sync guests
        $this->guestFactory->create()->sync();

        return $result;
    }

    /**
     * @return void
     */
    public function catalogSync()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_catalog_sync')) {
            return;
        }

        $this->catalogFactory->create()
            ->sync();
    }

    /**
     * CRON FOR EMAIL IMPORTER PROCESSOR.
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function emailImporter()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_importer')) {
            return;
        }

        $this->importerFactory->create()
            ->sync();
    }

    /**
     * Send integration insight data
     */
    public function sendIntegrationInsights()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_integration_insights')) {
            return;
        }

        $this->integrationInsights->create()
            ->sync();
    }

    /**
     * CRON FOR SYNC REVIEWS and REGISTER ORDER REVIEW CAMPAIGNS.
     *
     * @return void
     */
    public function reviewsAndWishlist()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_reviews_and_wishlist')) {
            return;
        }

        //sync reviews
        $this->reviewSync();
        //sync wishlist
        $this->cronHelper->wishlistSync();
    }

    /**
     * @return array
     */
    public function reviewSync()
    {
        return $this->cronHelper->reviewSync();
    }

    /**
     * CRON FOR ABANDONED CARTS.
     *
     * @return void
     */
    public function abandonedCarts()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_abandonedcarts')) {
            return;
        }

        $this->quoteFactory->create()->processAbandonedCarts();
        $this->abandonedCartProgramEnroller->process();
    }

    /**
     * CRON FOR AUTOMATION.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function syncAutomation()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_status')) {
            return;
        }

        $this->automationFactory->create()->sync();
    }

    /**
     * Send email campaigns.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendCampaigns()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_campaign')) {
            return;
        }

        $this->campaignFactory->create()->sendCampaigns();
    }

    /**
     * @return array|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function orderSync()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_order_sync')) {
            return;
        }

        // send order
        return $this->syncOrderFactory->create()
            ->sync();
    }

    /**
     * Cleaning for csv files and connector tables.
     */
    public function cleaning()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_cleaner')) {
            return;
        }

        //Clean tables
        $tables = [
            'automation' => Schema::EMAIL_AUTOMATION_TABLE,
            'importer' => Schema::EMAIL_IMPORTER_TABLE,
            'campaign' => Schema::EMAIL_CAMPAIGN_TABLE,
        ];
        $message = 'Cleaning cron job result :';

        foreach ($tables as $key => $table) {
            $this->importerResource->cleanup($table);
        }

        $archivedFolder = $this->fileHelper->getArchiveFolder();
        $this->fileHelper->deleteDir($archivedFolder);
    }

    /**
     * Check if already ran for same time
     *
     * @param string $jobCode
     * @return bool
     */
    private function jobHasAlreadyBeenRun($jobCode)
    {
        $currentRunningJob = $this->cronCollection->create()
            ->addFieldToFilter('job_code', $jobCode)
            ->addFieldToFilter('status', 'running')
            ->setPageSize(1);

        if ($currentRunningJob->getSize()) {
            $jobOfSameTypeAndScheduledAtDateAlreadyExecuted =  $this->cronCollection->create()
                ->addFieldToFilter('job_code', $jobCode)
                ->addFieldToFilter('scheduled_at', $currentRunningJob->getFirstItem()->getScheduledAt())
                ->addFieldToFilter('status', ['in' => ['success', 'failed']]);

            return ($jobOfSameTypeAndScheduledAtDateAlreadyExecuted->getSize()) ? true : false;
        }

        return false;
    }

    /**
     * @return void
     */
    public function syncEmailTemplates()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_email_templates')) {
            return;
        }

        $this->templateFactory->create()
            ->sync();
    }

    /**
     * @return void
     */
    public function monitor()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_monitor')) {
            return;
        }

        $this->monitor->create()
            ->run();
    }
}
