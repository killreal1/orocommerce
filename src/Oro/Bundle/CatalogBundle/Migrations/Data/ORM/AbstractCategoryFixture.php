<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\ORM;

use Doctrine\Common\Cache\FlushableCache;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads categories from predefined list
 */
abstract class AbstractCategoryFixture extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * Key is a category title, value is an array of categories
     *
     * @var array
     */
    protected $categories = [];

    /**
     * Keeping the assoc of category => SKU of the image.
     *
     * @var array
     */
    protected $categoryImages = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $defaultOrganization = $manager->getRepository(Organization::class)->getFirst();

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $manager->getRepository('OroCatalogBundle:Category');
        $root               = $categoryRepository->getMasterCatalogRoot($defaultOrganization);

        $this->addCategories($root, $this->categories, $this->categoryImages, $manager);

        $manager->flush();

        $this->generateSlugs($this->categories, $this->container->get('oro_redirect.generator.slug_entity'));

        $cache = $this->container->get('oro_redirect.url_cache');
        if ($cache instanceof FlushableCache) {
            $cache->flushAll();
        }
        $manager->flush();
    }

    /**
     * @param Category      $root
     * @param array         $categories
     * @param array         $images
     * @param ObjectManager $manager
     */
    protected function addCategories(Category $root, array $categories, array $images, ObjectManager $manager)
    {
        if (!$categories) {
            return;
        }

        $defaultOrganization = $root->getOrganization();

        foreach ($categories as $title => $nestedCategories) {
            $categoryTitle = new LocalizedFallbackValue();
            $categoryTitle->setString($title);

            $category = new Category();
            $category->addTitle($categoryTitle);
            $category->setOrganization($defaultOrganization);

            $slugString = $root->getParentCategory() ? $root->getSlugPrototype()->getString() . '/' . $title : $title;

            $slugPrototype = new LocalizedFallbackValue();
            $slugPrototype->setString(str_replace(' ', '-', strtolower($slugString)));
            $category->addSlugPrototype($slugPrototype);

            if (!empty($images[$title])) {
                if (isset($images[$title]['small'])) {
                    $category->setSmallImage($this->getCategoryImage($manager, $images[$title]['small']));
                }
                if (isset($images[$title]['large'])) {
                    $category->setLargeImage($this->getCategoryImage($manager, $images[$title]['large']));
                }
            }

            $manager->persist($category);

            $this->addReference($title, $category);

            $root->addChildCategory($category);

            $this->addCategories($category, $nestedCategories, $images, $manager);
        }
    }

    /**
     * @param array $categories
     * @param SlugEntityGenerator $slugEntityGenerator
     */
    private function generateSlugs(array $categories, SlugEntityGenerator $slugEntityGenerator)
    {
        foreach ($categories as $title => $nestedCategories) {
            /** @var Category $category */
            $category = $this->getReference($title);

            $slugEntityGenerator->generate($category, true);

            $this->generateSlugs($nestedCategories, $slugEntityGenerator);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param               $sku
     * @return null
     */
    protected function getCategoryImage(ObjectManager $manager, $sku)
    {
        $image   = null;
        $locator = $this->container->get('file_locator');

        try {
            $imagePath = $locator->locate(sprintf('@OroCatalogBundle/Migrations/Data/Demo/ORM/images/%s.jpg', $sku));

            if (is_array($imagePath)) {
                $imagePath = current($imagePath);
            }

            $fileManager = $this->container->get('oro_attachment.file_manager');
            $image       = $fileManager->createFileEntity($imagePath);
            $manager->persist($image);
        } catch (\Exception $e) {
            //image not found
        }

        return $image;
    }
}
