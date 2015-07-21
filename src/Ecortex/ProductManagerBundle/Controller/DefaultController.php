<?php

namespace Ecortex\ProductManagerBundle\Controller;

use Doctrine\ORM\NoResultException;
use Ecortex\ProductManagerBundle\Entity\ProductOption;
use Ecortex\ProductManagerBundle\Entity\RangePrice;
use Ecortex\ProductManagerBundle\Entity\Tag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Ecortex\ProductManagerBundle\Entity\Product;
use Symfony\Component\Security\Acl\Exception\Exception;

class DefaultController extends Controller
{

    public function indexAction() {
        //Fichier de test
        $file = "test.xlsx";

        //Appel du manager
        //$em = $this->getDoctrine()->getManager();

        //Appel et test du fournisseur
        $this->provider = $this->getProvider($file);

        //Initialisation de la lecture du fichier Excel
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject($file);
        $sheet = $phpExcelObject->getSheet(0);

        //Test du fichier et récupération des positions colonnes options et tags
        $pos = $this->getOptionsAndTags($sheet);

        //traitement des lignes
        $produits = $this->integrate($sheet, $pos);

        // create the writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'result-import.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return new Response($produits);


    }


    ///////////////////////////////////////////////////
    //         PRIVATE DU CONTROLLEUR               //
    //////////////////////////////////////////////////

    //Entetes obligatoires
    private $entetes = array(
        'produit',
        'ref',
        'description',
        'min',
        'max',
        'ht'
    );

    //Entetes optionnels (sous forme de structures de colonnes à suivre)
    private $structures = array(
        'option' => array('option_nom','option_valeur','option_prix'),
        'tag' => array('tag'),
    );


    private $provider;

    //Retourne le fournisseur
    private function getProvider($filename) {
        //@TODO extraire le nom de fichier
        $filename = "fournisseur1";

        $em = $this->getDoctrine()->getManager();
        if (! $provider = $em->getRepository('EcortexProductManagerBundle:Provider')->findOneByName($filename)) {
            throw new \Exception("Le fournisseur \"" . $filename . "\" n'existe pas. Déclarez le SVP.");
        }

        return $provider;
    }


    //Test les entetes de colonne selon la structure déclarée
    //retourne un tableau avec les indices des numéros de 1ere colonne de chaque structure
    private function getOptionsAndTags(\PHPExcel_Worksheet $sheet) {

        for ($i=0; $i<count($this->entetes); $i++) {
            $valeur = $sheet->getCellByColumnAndRow($i,1)->getValue();
            if (strtolower($valeur) != $this->entetes[$i]) {
                throw new \Exception("L'entête de la colonne " . ($i+1) . " devrait être du type \"" . $this->entetes[$i] ."\"");
            }
        }

        //Entetes suivants
        //retourne la position des colonnes de tag dans $tags[]
        //retourne la position des colonnes d'options dans $options[]

        //Demarrage du scan des colonne après celles d'entete
        $columnScan=count($this->entetes);
        $resultStructure = [];

        foreach ($this->structures as $keyStructure => $arrayOfValues) {
            $resultStructure[$keyStructure] = array();
        }


        while(null != $valeur = $sheet->getCellByColumnAndRow($columnScan,1)->getValue()) {
            //pour chacune des structures possible
            $flag = false;
            foreach ($this->structures as $keyStructure => $arrayOfValues) {
                if (in_array(strtolower($valeur), $arrayOfValues)) {
                    //test des valeurs des colonnes suivantes pour la structure en cours
                    for ($u = 1; $u < count($arrayOfValues); $u++) {
                        if ($arrayOfValues[$u] != strtolower(
                                $sheet->getCellByColumnAndRow($columnScan + $u, 1)->getValue()
                            )
                        ) {
                            throw new \Exception(
                                "L'entête de la colonne ".($columnScan + $u + 1)." devrait être de type \"".$arrayOfValues[$u]."\""
                            );
                        }
                    }
                    $resultStructure[$keyStructure][] = $columnScan;
                    $columnScan = $columnScan + count($arrayOfValues);
                    $flag = true;
                }
            }
            if (!$flag) {
                $acceptedValues =[];
                foreach ($this->structures as $key => $values) {
                    $acceptedValues[] = $key;
                }
                throw new \Exception(
                    "L'entête de la colonne ".($columnScan + 1)." devrait être du type \"".implode(
                        "\" ou \"",
                        $acceptedValues
                    )."\""
                );
            }
        }

        return $resultStructure;
    }



    //Lecture et traitement des lignes 1 à 1
    private function integrate(\PHPExcel_Worksheet $sheet, array $pos = null) {
        $ligne=2;
        $products = [];

        //Init de la structure minimal attendue d'un produit
        $masterStructure = [];
        foreach ($this->entetes as $entete) {
            $masterStructure[$entete] = '';
        }

        //Boucle sur chaque ligne pour tests et récup des valeurs
        while (null != $valeur = $sheet->getCellByColumnAndRow(0,$ligne)->getValue()) {
            if ($ligne == 20) {
                break;
            }

            $columnScanOffset = 0;
            foreach($masterStructure as $key => $value) {
                $readCell = $sheet->getCellByColumnAndRow(0+$columnScanOffset,$ligne)->getValue();

                //test si une valeur obligatoire est vide
                if ($key != "description" and (null == $readCell or $readCell=='')) {
                    throw new \Exception("Import annulé. La cellule ligne n°" . $ligne . " colonne n°" . ($columnScanOffset+1) . " ne peut être nulle ou vide.");
                }

                //test si min/max/ht est numeric
                if (($key == "min" or $key == "max" or $key == "ht") and (!is_numeric($readCell) or $readCell <= 0) ) {
                    throw new \Exception("Import annulé. La cellule ligne n°" . $ligne . " colonne n°" . ($columnScanOffset+1) . " doit être numérique et plus grand que 0.");
                }

                $masterStructure[$key] = $readCell;
                $columnScanOffset++;
            }

            //test de la validité du range (min <= max)
            if ($masterStructure['min'] > $masterStructure['max']) {
                throw new \Exception("Import annulé. La quantité mini ligne n°" . $ligne . " doit être inférieur ou égale à la quantité maxi.");
            }

            $products[] = $masterStructure;
            $ligne++;
        }

        //Init du manager
        $em = $this->getDoctrine()->getManager();

        //Création ou mise à jour des produits
        foreach ($products as $key => $product) {
            //attention $key correspond à la ligne n° $key+2

            //On commence par Tester l'existence du produit, le creer si besoin
            // et mettre à jour ses ranges de prix
            try {
                $existProduct = $em->getRepository('EcortexProductManagerBundle:Product')
                    ->getByRefAndProvider($product['ref'],$this->provider);

                $flag_range_ok = false;

                foreach($existProduct->getRangePrices() as $RPrice) {

                    //Si Range min max correspondent au product on met à jour celui-là et on sort
                    if ($product['min'] == $RPrice->getMin() and $product['max'] == $RPrice->getMax()) {
                        $RPrice->setPrice($product['ht']);
                        $flag_range_ok = true;
                        break;
                    }

                    //Si min et max < Range min on sort
                    if ($product['min'] < $RPrice->getMin() and $product['max'] < $RPrice->getMin()) {
                        continue;
                    }

                    //Si min et max > Range max on sort
                    if ($product['min'] > $RPrice->getMax() and $product['max'] > $RPrice->getMax()) {
                        continue;
                    }

                    //Dans tous les autres cas
                    throw new \Exception(
                            "Les quantités Min/Max de l'article " . $product['produit'] .
                            " référence: " . $product['ref'] .
                            " (ligne " . ($key+2) . //key+2 car key demarre à 0 qui correspond à la ligne 2
                            " ) chevauchent ou incluent une plage déjà existante en base de donnée.
                            Merci de modifier le fichier à importer où l'article en base");
                }

                if (!$flag_range_ok) {
                    $newRangePrice = new RangePrice();
                    $newRangePrice->setMin($product['min']);
                    $newRangePrice->setMax($product['max']);
                    $newRangePrice->setPrice($product['ht']);
                    $existProduct->addRangePrice($newRangePrice);
                }
                //traitement si tout est ok
                $existProduct->setName($product['produit']);
                $product['description'] != null ? $existProduct->setDescription($product['description']) : false ;
                $em->persist($existProduct);


            }
            catch (NoResultException $e) {
                $newProduct = new Product();
                $newProduct->setName($product['produit']);
                $newProduct->setRef($product['ref']);
                $newProduct->setDescription($product['description']);
                $newProduct->setProvider($this->provider);
                //le rangePrice est forcement nouveau
                $newRangePrice = new RangePrice();
                $newRangePrice->setMin($product['min']);
                $newRangePrice->setMax($product['max']);
                $newRangePrice->setPrice($product['ht']);
                $newProduct->addRangePrice($newRangePrice);
                $em->persist($newProduct);

            }

            //On flush le produit avec son range
            $em->flush();

            //Une fois flushé on recupere le Range en cours
            // pour mettre à jour les options
            try {
                $currentRange = $em->getRepository('EcortexProductManagerBundle:RangePrice')
                    ->getByProductRefProviderIdMinMax(
                        $product['ref'],
                        $this->provider->getId(),
                        $product['min'],
                        $product['max']
                    );
                $matchId = [];

                foreach($pos['option'] as $scanOption) {
                    $scanOptionOffset = 0;
                    $option = [];
                    foreach($this->structures['option'] as $optionItemName) {
                        $option[$optionItemName] = $sheet->getCellByColumnAndRow(
                            $scanOption + $scanOptionOffset,
                            $key + 2
                        )->getValue();
                        $scanOptionOffset++;
                    }

                    //On met à jour ou on insere l'option si elle porte un nom et une valeur
                    if (null != $option[$this->structures['option'][0]] and null != $option[$this->structures['option'][1]]) {

                        $match_Option_ok = false;

                        //Mise à jour
                        foreach ($currentRange->getProductOptions() as $productOption) {
                            if ($productOption->getName() == $option[$this->structures['option'][0]]
                            and $productOption->getValue() == $option[$this->structures['option'][1]]){
                                $productOption->setPrice($option[$this->structures['option'][2]]);

                                $em->persist($productOption);
                                $em->flush();
                                $matchId[] = $productOption->getId();
                                $match_Option_ok = true;
                            }
                        }

                        //Creation si inexistante
                        if (!$match_Option_ok) {
                            $newProductOption = new ProductOption();
                            $newProductOption->setName($option[$this->structures['option'][0]]);
                            $newProductOption->setValue($option[$this->structures['option'][1]]);
                            $newProductOption->setPrice($option[$this->structures['option'][2]]);

                            $currentRange->addProductOption($newProductOption);
                            $em->persist($currentRange);
                            $em->flush();
                            //si insertion on recup l'ID
                            $matchId[] = $newProductOption->getId();
                        }

                    }

                }


                //Suppresion des option non matchées
                foreach ($currentRange->getProductOptions() as $productOption) {
                    if (!in_array($productOption->getId(),$matchId)) {
                        $em->remove($productOption);
                    }
                }
                $em->flush();

            }
            catch (NoResultException $e) {
                throw new \Exception("Erreur lors de la lecture en Base du produit " . $product['produit'] .
                    " référence: " . $product['ref'] .
                    " (ligne " . ($key+2) . //key+2 car key demarre à 0 qui correspond à la ligne 2
                    " )");
            }

            //Maintenant on met à jour les Tags
            //tag a un traitement spéciale puisque le nombre de colonne est maxi 1

            //recupération de tous les tags de la ligne
            $tags = [];
            foreach($pos['tag'] as $scanTag) {
                if (null != $testtag = $sheet->getCellByColumnAndRow($scanTag, $key + 2)->getValue() )
                $tags[] = $testtag;
            }

            //Recuperation de tous les tag du RangePrice
            $existTags = [];
            foreach ($currentRange->getTags() as $existTag) {
                $existTags[] = $existTag->getName();
            }


            //On test si les tag du fichiers sont associés, sinon on le crée si besoin puis on associe
            foreach($tags as $tag) {
                if(null != $tag and !in_array($tag, $existTags)) {
                    if($findTag = $em->getRepository('EcortexProductManagerBundle:Tag')->findOneByName($tag)) {
                        $currentRange->addTag($findTag);
                    } else {
                        $newTag = new Tag();
                        $newTag->setName($tag);
                        $currentRange->addTag($newTag);
                    }
                    $em->persist($currentRange);
                }
            }

            //A l'envers maintenant: si des tags sont présents dans Range mais pas dans le fichier on remove
            foreach($currentRange->getTags() as $existTag) {
                if(!in_array($existTag->getName(),$tags)) {
                    $currentRange->removeTag($existTag);
                }
                $em->persist($currentRange);
            }

            $em->flush();


            $sheet->getCellByColumnAndRow(0,$key+2)->getStyle()->getFont()->getColor()->setRGB('00A000');


        }

        return var_dump("ok");
    }
}
