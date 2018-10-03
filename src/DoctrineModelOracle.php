<?php
/**
 * Copyright AUXITEC TECHNOLOGIES (groupe Artélia)
 */

namespace vdeApps\phpCore\DoctrineModel;

use Exception;

/**
 * Class DoctrineModelOracle
 * @package vdeApps\phpCore\DoctrineModel
 */
abstract class DoctrineModelOracle extends DoctrineModelAbstract
{
    
    /**
     * Retourne la prochaine valeur de la sequence
     *
     * @param $seqname
     *
     * @return nextval|false
     */
    public function nextval($seqname)
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder
            ->select($seqname . '.nextval AS NEXTVAL')
            ->from('dual');
        
        $res = $queryBuilder->execute();
        $rows = $res->fetchAll();
        
        try {
            return $rows[0]['NEXTVAL'];
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Retourne la valeur courante de la sequence
     *
     * @param $seqname
     *
     * @return currval|false
     */
    public function currval($seqname)
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder
            ->select($seqname . '.currval AS CURRVAL')
            ->from('dual');
        
        $res = $queryBuilder->execute();
        $rows = $res->fetchAll();
        
        try {
            return $rows[0]['CURRVAL'];
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Insertion
     *
     * @param array $aSet
     *
     * @return false|int lastinsertid
     * @throws Exception
     */
    public function create($aSet = [])
    {
        try {
            
            /**
             * Si une variable sequence a été definie dans la classe modele
             * ET que le PK n'existe pas dans le $aSet, alors on crée l'ID pour la PK
             */
            if (($seqname = $this->getSequence()) && !array_key_exists($this->getPk(), $aSet)) {
                $aSet[$this->getPk()] = $this->nextval($seqname);
            }
            
            parent::create($aSet);
        } catch (Exception $ex) {
            return false;
        }
        
        return true;
    }
}
