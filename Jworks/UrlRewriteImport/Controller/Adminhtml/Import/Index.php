<?php
/**
 * Url Rewrite Import admin controller
 *
 * @category    Jworks
 * @package     Jworks_UrlRewriteImport
 * @author Jitheesh V O <jitheeshvo@gmail.com>
 * @copyright Copyright (c) 2017 Jworks Digital ()
 */
namespace Jworks\UrlRewriteImport\Controller\Adminhtml\Import;
use Magento\Framework\Controller\ResultFactory;
/**
 * Class Index
 * @package Jworks\UrlRewriteImport\Controller\Adminhtml\Import
 */
class Index extends \Jworks\UrlRewriteImport\Controller\Adminhtml\Import
{

    /**
     * Import and export Page
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $resultPage->setActiveMenu('Jworks_UrlRewriteImport::url_import');
        $resultPage->addContent(
            $resultPage->getLayout()->createBlock('Jworks\UrlRewriteImport\Block\Adminhtml\Import\ImportHeader')
        );
        $resultPage->addContent(
            $resultPage->getLayout()->createBlock('Jworks\UrlRewriteImport\Block\Adminhtml\Import\Import')
        );

        return $resultPage;
    }
}
