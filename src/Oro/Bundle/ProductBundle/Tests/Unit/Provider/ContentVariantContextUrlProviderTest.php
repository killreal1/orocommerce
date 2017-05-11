<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Provider\ContentVariantContextUrlProvider;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentVariantContextUrlProviderTest extends \PHPUnit_Framework_TestCase
{
    const DATA = 'someData';

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestStack;

    /**
     * @var UrlStorageCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cache;

    /**
     * @var ContentVariantContextUrlProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->cache = $this->createMock(UrlStorageCache::class);
        $this->provider = new ContentVariantContextUrlProvider($this->requestStack, $this->cache);
    }

    public function testGetUrlWithoutRequest()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $this->assertNull($this->provider->getUrl(self::DATA));
    }

    public function testGetUrlWithoutUsedSlugKey()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request());
        $this->assertNull($this->provider->getUrl(self::DATA));
    }

    public function testGetUrlWithNoSlugByKey()
    {
        $request = new Request([], [], [ContentVariantContextUrlProvider::USED_SLUG_KEY => new \stdClass()]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->assertNull($this->provider->getUrl(self::DATA));
    }

    public function testGetUrlWithWrongRouteName()
    {
        $slug = new Slug();
        $slug->setRouteName('someRouteName');
        $request = new Request([], [], [ContentVariantContextUrlProvider::USED_SLUG_KEY => $slug]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->assertNull($this->provider->getUrl(self::DATA));
    }

    public function testGetUrlWithoutContentVariantIdKey()
    {
        $slug = new Slug();
        $slug->setRouteName(ProductCollectionContentVariantType::PRODUCT_COLLECTION_ROUTE_NAME);
        $request = new Request([], [], [ContentVariantContextUrlProvider::USED_SLUG_KEY => $slug]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->assertNull($this->provider->getUrl(self::DATA));
    }

    public function testGetUrlWithWrongContentVariantIdKey()
    {
        $slug = new Slug();
        $slug->setRouteName(ProductCollectionContentVariantType::PRODUCT_COLLECTION_ROUTE_NAME);
        $slug->setRouteParameters([ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => 'someWrongData']);
        $request = new Request([], [], [ContentVariantContextUrlProvider::USED_SLUG_KEY => $slug]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->assertNull($this->provider->getUrl(self::DATA));
    }

    public function testGetUrl()
    {
        $url = 'http://test.url';
        $slug = new Slug();
        $slug->setUrl($url);
        $slug->setRouteName(ProductCollectionContentVariantType::PRODUCT_COLLECTION_ROUTE_NAME);
        $slug->setRouteParameters([ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => self::DATA]);
        $request = new Request([], [], [ContentVariantContextUrlProvider::USED_SLUG_KEY => $slug]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertEquals($url, $this->provider->getUrl(self::DATA));
    }
}