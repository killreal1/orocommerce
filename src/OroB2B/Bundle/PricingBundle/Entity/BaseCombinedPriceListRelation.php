<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * @ORM\MappedSuperclass
 */
class BaseCombinedPriceListRelation implements WebsiteAwareInterface
{
    /**
     * @var CombinedPriceList
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList")
     * @ORM\JoinColumn(name="combined_price_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $priceList;

    /**
     * @var Website
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $website;

    /**
     * @var CombinedPriceList
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList")
     * @ORM\JoinColumn(name="full_combined_price_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $fullChainPriceList;

    /**
     * @return CombinedPriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @param CombinedPriceList $priceList
     * @return $this
     */
    public function setPriceList(CombinedPriceList $priceList)
    {
        $this->priceList = $priceList;
        if ($this->fullChainPriceList === null) {
            $this->fullChainPriceList = $priceList;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * {@inheritdoc}
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return CombinedPriceList
     */
    public function getFullChainPriceList()
    {
        return $this->fullChainPriceList;
    }

    /**
     * @param CombinedPriceList $fullChainPriceList
     */
    public function setFullChainPriceList(CombinedPriceList $fullChainPriceList)
    {
        $this->fullChainPriceList = $fullChainPriceList;
    }
}
