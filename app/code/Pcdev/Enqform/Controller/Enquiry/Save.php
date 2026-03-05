<?php
namespace Pcdev\Enqform\Controller\Enquiry;

class Save extends \Magento\Framework\App\Action\Action
{
	protected $_enquiryForm;

	public function __construct(
        \Magento\Framework\App\Action\Context $context,
		\Pcdev\Enqform\Model\PcdevEnquiryFormFactory $enquiryFormFactory
	)
	{
		$this->_enquiryForm = $enquiryFormFactory;
		parent::__construct($context);
	}

    public function execute()
    {		
		try {
			$enqFormPostData = $this->getRequest()->getParams();

			/*$enqFormPostData = [
				"name" => 'Raja',
				"email" => 'raja@test.com',
				"subject" => 'Generl',
				"mobileno" => '8010120120',
				"comment" => 'Test'		
			];*/
		
			$modelEnqForm = $this->_enquiryForm->create();		
			$modelEnqForm->addData([
				"name" => $enqFormPostData['name'],
				"email" => $enqFormPostData['email'],
				"subject" => $enqFormPostData['subject'],
				"mobileno" => $enqFormPostData['mobileno'],
				"comment" => $enqFormPostData['comment']
				]);
			$saveEnqFormData = $modelEnqForm->save();
			
			$this->messageManager->addSuccessMessage(__("Data Saved Successfully."));
			
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e, __("We can\'t submit your request, Please try again."));
        }			

		$resultRedirect = $this->resultRedirectFactory->create();
		$resultRedirect->setPath('enquiryinf/enquiry/form');
		return $resultRedirect;
	}
}