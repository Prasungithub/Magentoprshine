<?php 
    
    namespace Pcdev\Ratingoverrite\Block\Product;
	
	//use Magento\Catalog\Api\ProductRepositoryInterface;
    use Magento\Review\Model\ResourceModel\Review\Collection as ReviewCollection;

    class View extends \Magento\Catalog\Block\Product\View
{
    /**
     * Review collection
     *
     * @var ReviewCollection
     */
    protected $_reviewsCollection;

    /**
     * Review resource model
     *
     * @var \Magento\Review\Model\ResourceModel\Review\CollectionFactory
     */
    protected $_reviewsColFactory;
	protected $_reviewFactory;
	protected $_objectReview;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Review\Model\ResourceModel\Review\CollectionFactory $collectionFactory
	 * @param \Magento\Review\Model\Review $reviewFactory
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
	 
	 
	 
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $collectionFactory,
		\Magento\Review\Model\Review $reviewFactory,
        array $data = []
    ) {
        $this->_reviewsColFactory = $collectionFactory;
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        $product = $this->getProduct();

        if (!$product) {
            return '';
        }

        $product->setShortDescription(null);

        return parent::_toHtml();
    }

    /**
     * Replace review summary html with more detailed review summary
     *
     * Reviews collection count will be jerked here
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $templateType
     * @param bool $displayIfNoReviews
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getReviewsSummaryHtml(
        \Magento\Catalog\Model\Product $product,
        $templateType = false,
        $displayIfNoReviews = false
    ) {
        return $this->getLayout()->createBlock(
            \Magento\Review\Block\Rating\Entity\Detailed::class
        )->setEntityId(
            $this->getProduct()->getId()
        )->toHtml() . $this->getLayout()->getBlock(
            'product_review_list.count'
        )->assign(
            'count',
            $this->getReviewsCollection()->getSize()
        )->toHtml();
    }

    /**
     * Get collection of reviews
     *
     * @return ReviewCollection
     */
    public function getReviewsCollection()
    {
        if (null === $this->_reviewsCollection) {
            $this->_reviewsCollection = $this->_reviewsColFactory->create()->addStoreFilter(
                $this->_storeManager->getStore()->getId()
            )->addStatusFilter(
                \Magento\Review\Model\Review::STATUS_APPROVED
            )->addEntityFilter(
                'product',
                $this->getProduct()->getId()
            )->setDateOrder();
        }
        return $this->_reviewsCollection;
    }

    /**
     * Force product view page behave like without options
     *
     * @return bool
     */
    public function hasOptions()
    {
        return false;
    }
	public function getAllStart($productId) {
		
		$review = $this->_reviewsColFactory->create()
        ->addStatusFilter(
            \Magento\Review\Model\Review::STATUS_APPROVED
        )->addEntityFilter(
            'product',
            $productId
        )->setDateOrder()
		->addStoreFilter($this->_storeManager->getStore()->getId())
		->addFieldToSelect('review_id');
		

    $review->getSelect()->columns('detail.detail_id')->joinInner(
            ['vote' => $review->getTable('rating_option_vote')], 'main_table.review_id = vote.review_id', array('review_value' => 'vote.value')
    );
    $review->getSelect()->order('review_value DESC');
    $review->getSelect()->columns('count(vote.vote_id) as total_vote')->group('review_value');
    for ($i = 5; $i >= 1; $i--) {
        $arrRatings[$i]['value'] = 0;
    }
    foreach ($review as $_result) {
        $arrRatings[$_result['review_value']]['value'] = $_result['total_vote'];
    }
    return $arrRatings;
    }
    }