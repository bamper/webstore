<?php

namespace Ecortex\ProductManagerBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;


use Ecortex\ProductManagerBundle\Entity\ImportFile\FindItem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Ecortex\ProductManagerBundle\Entity\ProductOption;
use Ecortex\ProductManagerBundle\Entity\RangePrice;
use Ecortex\ProductManagerBundle\Entity\Tag;
use Ecortex\ProductManagerBundle\Entity\Product;
use Ecortex\ProductManagerBundle\Entity\ImportFile\FileMasterStructure;
use Ecortex\ProductManagerBundle\Entity\ImportFile\ImportFile;
use Ecortex\ProductManagerBundle\Entity\ImportFile\StructureItem;
use Ecortex\ProductManagerBundle\Entity\VarGate;

class DefaultController extends Controller
{

    public function indexAction() {
        return $this->render('EcortexProductManagerBundle:Default:index.html.twig');
    }

    public function progressAction() {
        $em = $this->getDoctrine()->getManager()->getRepository('EcortexProductManagerBundle:VarGate');
        $varGate = $em->findOneBySessid('progress');
        $progress = $varGate->getValue();
        $response = new Response();
        $response->setStatusCode(200);
        $response->headers->set('Content-Type','application/json' );
        $response->setContent($progress);
        return $response;
    }

    public function importAction() {
        session_write_close();
        set_time_limit(600) ;


        /*
         * init VarGate for Ajax MultiAsynchronous Reading State
         */

        $em = $this->getDoctrine()->getManager();
        if(!$varGate=$em->getRepository('EcortexProductManagerBundle:VarGate')->findOneBySessid($this->varGateSessid)){
            $varGate = new VarGate();
            $varGate->setSessid($this->varGateSessid);
            $em->persist($varGate);
        }
        $varGate->setValue(json_encode(array(
            'percent' => 0,
            'currentRow' => 0,
            'totalRow' => '?',
            'elapseTime' => 0,
            'averageTime' => 0,
            'remaining' => '?',
            'elementTime'
            )));
        $em->flush();


        /*
         * Test file
         */
        $fileName = "goldstar.csv";


        /*
         * init response
         */
        $response = new Response();



        /*
         * getting & init Product Provider
         */
        try {
            $this->getProvider($fileName);
        } catch(\Exception $e) {
            return $response = $this->makeResponse($e->getMessage(), $response);
            exit;
        }


        /*
         * Open File and process
         */
        if(($handle = fopen($fileName,'r')) !== false){
            try {
                /*Passage des arguments ressources aux privates du controller*/
                $this->initFile($fileName, $handle);

                /*recup et verif des entetes*/
                $this->getOptionsAndTags();

                /*integration*/
                $this->integrate(
                    $this->container->getParameter('ecortex_product_manager.beginlinetest'),
                    $this->container->getParameter('ecortex_product_manager.nblinestest')
                );
            } catch(\Exception $e) {
                set_time_limit(120) ;
                return $response = $this->makeResponse($e->getMessage(), $response);
                exit;
            }
        };


        /*
         * return new Response(var_dump($temp));
         */
        set_time_limit(120) ;
        $response->setStatusCode(200);
        $response->setContent("Requete terminée avec succès");
        return $response;
    }


    ///////////////////////////////////////////////////
    //         PRIVATE DU CONTROLLEUR               //
    //////////////////////////////////////////////////

    /**
     * @var ImportFile
     */
    private $importFile;
    private $providerId;
    private $currentRow = 0;
    private $varGateSessid="progress";

    //Make response
    private function makeResponse($e, Response $response) {
            $response->setStatusCode(500);
            $response->setContent("La requete a echoué:".$e);
            return $response;
    }

    //init importFileStructure
    private function initFile($fileName, $handle) {
        $this->importFile = new ImportFile();
        $this->importFile->setName($fileName);
        $this->importFile->setHandle($handle);

        //define new handle for line count
        $linecount=0;
        if(($handle2 = fopen($fileName,'r')) !== false){
            while(($lineArray = fgetcsv($handle2,null,';')) != false){
                $linecount++;
            }
            fclose($handle2);
            $this->importFile->setLineCount($linecount);
        }

        $struc1 = new FileMasterStructure();

        $item1 = new StructureItem("master" ,array(
            'produit',
            'ref',
            'description',
            'min',
            'max',
            'ht'
        ));


        $item2 = new StructureItem("option", array(
            'option_nom',
            'option_valeur',
            'option_prix',
        ));
        $findItem2 = new FindItem($item2->getName());


        $item3 = new StructureItem("tag", array(
            'tag',
        ));
        $findItem3 = new FindItem($item3->getName());

        $struc1->addItem($item1);
        $struc1->addItem($item2);
        $struc1->addItem($item3);

        $this->importFile->setStructure($struc1);
        $this->importFile->addFindItem($findItem2);
        $this->importFile->addFindItem($findItem3);


    }

    //return UTF-8 items Array
    private function lineRead ($handle) {
        if(($lineArray = fgetcsv($handle,null,';')) == false){
            fclose($handle);
            return false;
        }
        $this->currentRow++;
        $newLine = [];
        foreach ($lineArray as $item) {

            $newLine[] = iconv("Windows-1252", "UTF-8//IGNORE", $item);
        }

        return $newLine;
    }

    //Retourne le fournisseur
    private function getProvider($filename) {
        try {
            //@TODO extraire le nom de fichier
            $filename = "goldstar";
            $em = $this->getDoctrine()->getManager();
            if (!$provider = $em->getRepository('EcortexProductManagerBundle:Provider')->findOneByName($filename)) {
                throw new \Exception("Le fournisseur \"".$filename."\" n'existe pas. Déclarez le SVP.");
            }

            $this->providerId = $provider->getId();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }


    //Test les entetes de colonne selon la structure déclarée
    //hydrate importFile avec des findItem contenant les numeros des colonnes des debuts de structures optionnelles
    private function getOptionsAndTags() {
        try {

            //Lecture de la 1ere ligne du fichier
            $fileHeaders = $this->lineRead($this->importFile->getHandle());
            //1ere Structure à comparer: la structure master obligatoire
            $masterStruct = $this->importFile->getStructure()->getItem('master');

            //Test si le Header contient au moins autant d'elements que la structure
            if ($masterStruct->getColumnCount() > count($fileHeaders)) {
                $tempText = '';
                $i = 0;
                foreach ($masterStruct->getColumnNames() as $columnName) {
                    $i++;
                    $tempText = $tempText."Colonne n°".$i."-> \"".$columnName."\"\n";
                }
                throw new \Exception(
                    "Les 1ers entetes de colonnes attendues du fichier doivent être les suivantes:\n".$tempText
                );
            }

            //Test une à une les colonnes de type master
            for ($i = 0; $i < $masterStruct->getColumnCount(); $i++) {
                $valeur = $fileHeaders[$i];
                if (mb_strtolower($valeur, 'UTF-8') != $masterStruct->getColumnNames()[$i]) {
                    throw new \Exception(
                        "L'entête de la colonne ".($i + 1)." devrait être du type \"".$masterStruct->getColumnNames(
                        )[$i]."\""
                    );
                }
            }

            //Recherche des structures d'entetes (type option, tag...) dans les colonnes suivantes
            //declare le type et la position de la colonne dans FindItem d'ImportFile

            $optionnalStructures = [];
            foreach ($this->importFile->getStructure()->getItems() as $keyStructure => $structure) {
                if ($keyStructure != 'master') {
                    $optionnalStructures[] = $structure;
                }
            }

            //Pour chacune des colonnes du fichier de la n°(fin master) à la n°(fin fichier)
            for ($columnScan = $masterStruct->getColumnCount(); $columnScan < count($fileHeaders); $columnScan++) {
                //pour chacune des structures possible
                $is_struct_head = false;
                $valeur = $fileHeaders[$columnScan];
                foreach ($optionnalStructures as $structure) {
                    if (mb_strtolower($valeur, 'UTF-8') == $structure->getColumnNames()[0]) {
                        //test des valeurs des colonnes suivantes pour la structure en cours
                        for ($u = 1; $u < $structure->getColumnCount(); $u++) {
                            if ($structure->getColumnNames()[$u] != mb_strtolower(
                                    $fileHeaders[$columnScan + $u],
                                    'UTF-8'
                                )
                            ) {
                                throw new \Exception(
                                    "L'entête de la colonne ".($columnScan + $u + 1)." devrait être de type \"".$structure->getColumnNames(
                                    )[$u]."\""
                                );
                            }
                        }

                        $this->importFile->getFindItem($structure->getName())->addValues($columnScan);

                        $columnScan = $columnScan + $structure->getColumnCount() - 1;
                        $is_struct_head = true;
                    }
                }
                if (!$is_struct_head) {
                    $acceptedValues = [];
                    foreach ($optionnalStructures as $structure) {
                        $acceptedValues[] = $structure->getColumnNames()[0];
                    }
                    throw new \Exception(
                        "L'entête de la colonne ".($columnScan + 1)." devrait être du type \"".implode(
                            "\" ou \"",
                            $acceptedValues
                        )."\""
                    );
                }
            }
            return $e = false;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    //Lecture et traitement des lignes 1 à 1
    private function integrate($beginTest = 0, $nbTest = 0) {
        try {
            //TimeStart
            $timeStart =  microtime(true);

            //Boucle sur chaque ligne pour tests et récup des valeurs
            while ($fileValues = $this->lineRead($this->importFile->getHandle())) {
                if ($this->currentRow < $beginTest) {continue;}
                $this->integrateOneLine($fileValues, $timeStart, $beginTest);
                if ($nbTest !=0 and $this->currentRow == $beginTest+$nbTest){break;}
            }

            return $e = false;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private function integrateOneLine($fileValues, $timeStart, $beginTest) {
        try{

            $em = $this->getDoctrine()->getManager();
            $elementTimeStart = microtime(true);


            $columnScanOffset = 0;
            $masterValues =[]; //seront stockées ici les valeurs master du produit lu dans le fichier
            $optionnalValues = []; //seront stockées ici les valeurs optionnelles du produits lu dans le fichier

            //Récupération de la structure master obligatoire attendue
            $masterStructure = $this->importFile->getStructure()->getItem('master');

            //Lecture des colonnes du fichier / tests et stockage des valeurs
            foreach($masterStructure->getColumnNames() as $columnName) {

                $readValue =$fileValues[$columnScanOffset];
                $readValue = preg_replace('#^€\s+(\d+)[,|\.](\d+)$#','$1.$2',$readValue);
                $readValue = preg_replace('#^(\d+)[,|\.](\d+)\s+€$#','$1.$2',$readValue);
                $readValue = preg_replace('#^€(\d+),(\d+)$#','$1.$2',$readValue);
                $readValue = preg_replace('#^(\d+),(\d+)€$#','$1.$2',$readValue);
                //test si une valeur obligatoire est vide
                if ($columnName != "description" and (null == $readValue or $readValue=='')) {
                    throw new \Exception("Import annulé. La cellule ligne n°" . ($this->currentRow) . " colonne n°" . ($columnScanOffset+1) . " ne peut être nulle ou vide.");
                }

                //test si min/max/ht est numeric

                if (($columnName == "min" or $columnName == "max" or $columnName == "ht") and (!is_numeric(
                            $readValue
                        ) or $readValue <= 0)
                ) {
                    throw new \Exception(
                        "Import annulé. La cellule ligne n°".
                        ($this->currentRow)." colonne n°".($columnScanOffset + 1).
                        "(".$readValue.") doit être numérique et plus grand que 0."
                    );
                }

                $masterValues[$columnName] = $readValue;
                $columnScanOffset++;
            }
            //Unsetting unused var
            unset($columnName);
            unset($readValue);



            //test de la validité du range (min <= max)
            if ($masterValues['min'] > $masterValues['max']) {
                throw new \Exception("Import annulé. La quantité mini ligne n°" . ($this->currentRow) . " doit être inférieur ou égale à la quantité maxi.");
            }


            //Structure mineure (pas de tests car non bloquant)
            $optionnalStructure = $this->importFile->getFindItems();
            $optionnalValues = [];
            foreach ($optionnalStructure as $keyStructure => $structure) {
                $optionnalValues[$keyStructure] = [];
                foreach ($structure->getValues() as $scanOption) {
                    $scanOptionOffset = 0;
                    $option = [];
                    foreach ($this->importFile->getStructure()->getItem($keyStructure)->getColumnNames() as $optionItemName) {

                        $option[$optionItemName] = $fileValues[$scanOption + $scanOptionOffset];
                        $scanOptionOffset++;
                    }

                    $optionnalValues[$keyStructure][] = $option;
                    //Unsetting unused var
                    unset($optionItemName);

                }
                //Unsetting unused var
                unset($scanOption);
                unset($scanOptionOffset);
                unset($option);
            }
            //Unsetting unused var
            unset($keyStructure);
            unset($structure);

            //L'objet Product de la ligne CSV est le suivant
            $product = array(
                'masterStructure' => $masterValues,
                'optionnalStructure' => $optionnalValues,
            );


            //On va le fluscher en base

            //On commence par Tester l'existence du produit, le creer si besoin
            // et mettre à jour ses ranges de prix
            try {
                $existProduct = $em->getRepository('EcortexProductManagerBundle:Product')
                    ->getByRefAndProvider($product['masterStructure']['ref'],$this->providerId);

                $flag_range_ok = false;

                foreach($existProduct->getRangePrices() as $RPrice) {

                    //Si Range min max correspondent au product on met à jour celui-là et on sort
                    if ($product['masterStructure']['min'] == $RPrice->getMin() and $product['masterStructure']['max'] == $RPrice->getMax()) {
                        $RPrice->setPrice(number_format($product['masterStructure']['ht'],2,'.',''));
                        $flag_range_ok = true;
                        break;
                    }

                    //Si min et max < Range min on sort
                    if ($product['masterStructure']['min'] < $RPrice->getMin() and $product['masterStructure']['max'] < $RPrice->getMin()) {
                        continue;
                    }

                    //Si min et max > Range max on sort
                    if ($product['masterStructure']['min'] > $RPrice->getMax() and $product['masterStructure']['max'] > $RPrice->getMax()) {
                        continue;
                    }

                    //Dans tous les autres cas
                    throw new \Exception(
                        "Les quantités Min/Max de l'article " . $product['masterStructure']['produit'] .
                        " référence: " . $product['masterStructure']['ref'] .
                        " (ligne " . ($this->currentRow+1) .
                        " ) chevauchent ou incluent une plage déjà existante en base de donnée.
                                Merci de modifier le fichier à importer ou l'article en base");
                }
                //Unsetting unused var
                unset($RPrice);

                if (!$flag_range_ok) {
                    $newRangePrice = new RangePrice();
                    $newRangePrice->setMin((int)$product['masterStructure']['min']);
                    $newRangePrice->setMax((int)$product['masterStructure']['max']);
                    $newRangePrice->setPrice(number_format($product['masterStructure']['ht'],2,'.',''));
                    $em->persist($newRangePrice);
                    $existProduct->addRangePrice($newRangePrice);
                }



                //traitement si tout est ok
                $existProduct->setName($product['masterStructure']['produit']);
                $product['masterStructure']['description'] != null ? $existProduct->setDescription($product['masterStructure']['description']) : false ;


                //On flush le produit avec son range
                $em->flush();

                //Unsetting unused var
                unset($flag_range_ok);
                unset($newRangePrice);
                unset($existProduct);


            }
            catch (NoResultException $e) {
                $newProduct = new Product();
                $newProduct->setName($product['masterStructure']['produit']);
                $newProduct->setRef($product['masterStructure']['ref']);
                $newProduct->setDescription($product['masterStructure']['description']);
                $newProduct->setProvider($em->getRepository('EcortexProductManagerBundle:Provider')->findOneById($this->providerId));
                //le rangePrice est forcement nouveau
                $newRangePrice = new RangePrice();
                $newRangePrice->setMin((int)$product['masterStructure']['min']);
                $newRangePrice->setMax((int)$product['masterStructure']['max']);
                $newRangePrice->setPrice(number_format($product['masterStructure']['ht'],2,'.',''));
                $newProduct->addRangePrice($newRangePrice);
                $em->persist($newProduct);

                //On flush le produit avec son range
                $em->flush();

                //Unsetting unused var
                unset($newRangePrice);
                unset($newProduct);

            }


            //Une fois flushé on recupere le Range en cours
            // pour mettre à jour les options
            //Quelques règles: le nom indice 0 et la valeur sont systématiquement mb_strtolower UTF-8 et price=0 si null ou ''
            try {
                $currentRange = $em->getRepository('EcortexProductManagerBundle:RangePrice')
                    ->getByProductRefProviderIdMinMax(
                        $product['masterStructure']['ref'],
                        $this->providerId,
                        $product['masterStructure']['min'],
                        $product['masterStructure']['max']
                    );
                $matchId = [];

                foreach($product['optionnalStructure']['option'] as $option) {
                    //On met à jour ou on insere l'option si elle porte un nom et une valeur
                    if (null != $option[$this->importFile->getStructure()->getItem('option')->getColumnNames()[0]]
                        and null != $option[$this->importFile->getStructure()->getItem('option')->getColumnNames()[1]]) {

                        $match_Option_ok = false;

                        //Mise à jour
                        foreach ($currentRange->getProductOptions() as $productOption) {
                            if ($productOption->getName() == mb_strtolower($option[$this->importFile->getStructure()->getItem('option')->getColumnNames()[0]],'UTF-8')
                                and $productOption->getValue() == mb_strtolower($option[$this->importFile->getStructure()->getItem('option')->getColumnNames()[1]],'UTF-8')){

                                if (is_null($option[$this->importFile->getStructure()->getItem('option')->getColumnNames()[2]])
                                    or $option[$this->importFile->getStructure()->getItem('option')->getColumnNames()[2]]=='') {
                                    $option[$this->importFile->getStructure()->getItem('option')->getColumnNames()[2]]=0;
                                }

                                $productOption->setPrice(number_format($option[$this->importFile->getStructure()->getItem('option')->getColumnNames()[2]],2,'.',''));
                                $matchId[] = $productOption->getId();
                                $match_Option_ok = true;
                            }
                        }

                        $em->flush();

                        //Unsetting unused var
                        unset($productOption);

                        //Creation si inexistante
                        if (!$match_Option_ok) {
                            $newProductOption = new ProductOption();
                            $newProductOption->setName(mb_strtolower($option[$this->importFile->getStructure()->getItem('option')->getColumnNames()[0]],'UTF-8'));
                            $newProductOption->setValue(mb_strtolower($option[$this->importFile->getStructure()->getItem('option')->getColumnNames()[1]],'UTF-8'));
                            $newProductOption->setPrice($option[$this->importFile->getStructure()->getItem('option')->getColumnNames()[2]]);
                            $em->persist($newProductOption);
                            $currentRange->addProductOption($newProductOption);

                            $em->flush();
                            //si insertion on recup l'ID
                            $matchId[] = $newProductOption->getId();
                        }
                        //Unsetting unused var
                        unset($newProductOption);

                    }

                    //Unsetting unused var
                    unset($match_Option_ok);


                }

                //Unsetting unused var
                unset($option);

                //Suppresion des option non matchées
                foreach ($currentRange->getProductOptions() as $productOption) {
                    if (!in_array($productOption->getId(),$matchId)) {
                        $em->remove($productOption);
                    }
                }
                $em->flush();

                //Unsetting unused var
                unset($productOption);
                unset($matchId);
                unset($currentRange);

            }
            catch (NoResultException $e) {
                throw new \Exception("Erreur lors de la lecture en Base du produit " . $product['masterStructure']['produit'] .
                    " référence: " . $product['masterStructure']['ref'] .
                    " (ligne " . ($this->currentRow) .
                    " )");
            }

            //Maintenant on met à jour les Tags
            //recupération de tous les tags de la ligne
            try {
                $currentRange = $em->getRepository('EcortexProductManagerBundle:RangePrice')
                    ->getByProductRefProviderIdMinMax(
                        $product['masterStructure']['ref'],
                        $this->providerId,
                        $product['masterStructure']['min'],
                        $product['masterStructure']['max']
                    );

                $tagArray = [];
                foreach ($product['optionnalStructure']['tag'] as $tags) {
                    if (null != $tags[$this->importFile->getStructure()->getItem('tag')->getColumnNames(
                        )[0]] and $tags[$this->importFile->getStructure()->getItem('tag')->getColumnNames(
                        )[0]] != ''
                    ) {
                        $tagArray[] = mb_strtolower(
                            $tags[$this->importFile->getStructure()->getItem('tag')->getColumnNames()[0]],
                            'UTF-8'
                        );
                    }
                }
                //Unsetting unused var
                unset($tags);

                $tagArray = array_unique($tagArray);


                //Recuperation de tous les tag du RangePrice
                $existTags = [];
                foreach ($currentRange->getTags() as $existTag) {
                    $existTags[] = $existTag->getName();
                }
                //Unsetting unused var
                unset($existTag);


                //On test si les tag du fichiers sont associés, sinon on le crée si besoin puis on associe
                foreach ($tagArray as $tag) {

                    if (!in_array($tag, $existTags)) {
                        if ($findTag = $em->getRepository('EcortexProductManagerBundle:Tag')->findOneByName($tag)) {
                            $currentRange->addTag($findTag);
                        } else {
                            $newTag = new Tag();
                            $newTag->setName($tag);
                            $em->persist($newTag);
                            $currentRange->addTag($newTag);
                        }

                        $em->flush();

                        //Unsetting unused var
                        unset($findTag);
                        unset($newTag);
                    }
                }
                //Unsetting unused var
                unset($tag);

                //A l'envers maintenant: si des tags sont présents dans Range mais pas dans le fichier on remove
                foreach ($currentRange->getTags() as $existTag) {
                    if (!in_array($existTag->getName(), $tagArray)) {
                        $currentRange->removeTag($existTag);
                    }
                }
                $em->flush();

                //Unsetting unused var
                unset($existTag);
                unset($tagArray);
                unset($existTags);
                unset($currentRange);

            } catch (NoResultException $e) {
                throw new \Exception("Erreur lors de la lecture en Base du produit " . $product['masterStructure']['produit'] .
                    " référence: " . $product['masterStructure']['ref'] .
                    " (ligne " . ($this->currentRow) .
                    " )");
            }



            $actualTime = microtime(true);
            $elapseTime = $actualTime-$timeStart;
            $averageTime = $elapseTime/($this->currentRow-($beginTest-1));
            $remaining = ($this->importFile->getLineCount()-$this->currentRow)*$averageTime;
            $elementTime = $actualTime-$elementTimeStart;

            $progress = json_encode(array(
                'percent' => (int)(($this->currentRow/$this->importFile->getLineCount())*100),
                'currentRow' => $this->currentRow,
                'totalRow' => $this->importFile->getLineCount(),
                'elapseTime' => $elapseTime,
                'averageTime' => $averageTime,
                'remaining' => $remaining,
                'elementTime' => $elementTime
            ));

            $varGate = $em->getRepository('EcortexProductManagerBundle:VarGate')->findOneBySessid($this->varGateSessid);
            $varGate->setValue($progress);
            $em->flush();


            //Unsetting unused var
            unset($elapseTime);
            unset($actualTime);
            unset($averageTime);
            unset($elementTimeStart);
            unset($elementTime);
            unset($remaining);
            unset($progress);
            unset($product);
            unset($masterStructure);
            unset($columnScanOffset);
            unset($masterValues);
            unset($optionnalStructure);
            unset($optionnalValues);
            $em->clear();



            return $e = false;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
