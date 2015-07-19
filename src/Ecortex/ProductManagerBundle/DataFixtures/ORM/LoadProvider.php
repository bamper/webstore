<?php

namespace OC\PlatformBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ecortex\ProductManagerBundle\Entity\Provider;

class LoadFeature extends AbstractFixture implements OrderedFixtureInterface, FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        // List of Features
        $providers = array(
            'Fournisseur1',
            'Fournisseur2',
            'Fournisseur3',
            'Fournisseur4',
        );


        foreach ($providers as $provider) {
            $newProvider = new Provider();
            $newProvider->setName($provider);

            $manager->persist($newProvider);
        }

        $manager->flush();
    }

    public function getOrder() {
        return 0;
    }
}