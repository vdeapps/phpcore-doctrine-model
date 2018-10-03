<?php
/**
 * Copyright (c) vdeApps 2018
 */

namespace vdeApps\phpCore\DoctrineModel;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use vdeApps\phpCore\ChainedArray;
use vdeApps\phpCore\Datetime;
use vdeApps\phpCore\Dictionary\Dictionary;
use vdeApps\phpCore\Dictionary\DictionaryInterface;
use vdeApps\phpCore\Helper;
use Exception;

abstract class DoctrineModelAbstract implements DoctrineModelInterface
{
    public $settings = [
        'tablename' => false,
        'viewname'  => false,
        'pk'        => false,
        'fields'    => false,
        'vfields'   => false,
        'sequence'  => null,
    ];
    
    /** @var ChainedArray $lastMessage */
    protected $lastMessage = null;
    
    /** @var DictionaryInterface */
    protected $dictionary;
    /**
     * @var Connection $conn
     */
    protected $conn;
    /**
     * @var QueryBuilder
     */
    protected $qb;
    protected $rows = null;
    private $filters = [];
    private $fieldsSelect = false;   //Tableau des filtres
    
    /**
     * PagesController constructor.
     *
     * @param Connection $conn
     *
     * @throws Exception
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->qb = $this->newQB();
        $this->dictionary = new Dictionary();
        $this->lastMessage = ChainedArray::getInstance();
        
        return $this;
    }      //Querybuilder
    
    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed  $value The value to convert.
     * @param string $empty
     *
     * @return mixed The database representation of the value.
     */
    public static function convertToDatabaseValue($value, $empty = null)
    {
        if (is_null($value)) {
            $value = 'NULL';
        } elseif (empty($value)) {
            $value = (is_null($empty)) ? 'NULL' : self::convertToDatabaseValue($empty);
        } elseif (is_string($value)) {
            $value = '\'' . str_replace('\'', '\'\'', $value) . '\'';
        }
        
        return $value;
    }
    
    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed $value The value to convert.
     *
     * @return mixed The PHP representation of the value.
     */
    public static function convertToPHPValue($value)
    {
        return $value;
    }
    
    /**
     * Test si la valeur correspond au type, remplacé par NULL si besoin
     *
     * @param mixed $value
     * @param Type  $type
     *
     * @return bool
     */
    public static function valideValue(&$value, $type)
    {
        
        /*
         * Valeur NULL autorisée
         */
        if (is_null($value)) {
            return true;
        }
        
        switch ($type) {
            case Type::INTEGER:
            case Type::BOOLEAN:
            case Type::DECIMAL:
            case Type::FLOAT:
                // Si le champ est vide on remplace par NULL
                if ($value == '') {
                    $value = null;
                    
                    return true;
                }
                if (is_numeric($value) || is_float($value) || is_bool($value)) {
                    return true;
                }
                break;
            
            case Type::TEXT:
            case Type::STRING:
                return is_string($value);
                break;
            
            /*
             * Pour les dates on teste et réécrit la valeur SQL
             */
            case Type::DATE:
            case Type::DATETIME:
                $dt = new Datetime();
                
                // Si le champ est vide on remplace par NULL
                if ($value == '') {
                    $value = null;
                    
                    return true;
                } elseif (strtoupper($value) === 'NOW') {
                    $value = $dt->set_date()->toSql();
                    
                    return true;
                } else {
                    if ($dt->set_date($value)) {
                        $value = $dt->toSql();
                        
                        return true;
                    }
                    
                    //Sinon
                    return false;
                }
                break;
            
            default:
                return true;
        }
        
        return false;
    } // Liste des champs à sélectionner
    
    /**
     * @return QueryBuilder
     */
    public function getQb()
    {
        return $this->qb;
    }
    
    /**
     * Create a new QueryBuilder
     * @return QueryBuilder
     */
    public function newQB()
    {
        $this->qb = $this->conn->createQueryBuilder();
        
        return $this->qb;
    }
    
    /**
     * @return DictionaryInterface
     */
    public function getDictionary()
    {
        return $this->dictionary;
    }
    
    /**
     * @param DictionaryInterface $dictionary
     *
     * @throws Exception
     */
    public function setDictionary($dictionary)
    {
        $this->dictionary->append($dictionary);
    }
    
    /**
     * Return the last message
     * @return ChainedArray
     */
    public function getMessage()
    {
        return $this->lastMessage;
    }
    
    /**
     * Set tablename
     *
     * @param $tablename
     *
     * @return $this
     */
    public function setTableName($tablename)
    {
        $this->settings['tablename'] = $tablename;
        if (false === $this->settings['viewname']) {
            $this->setViewName($tablename);
        }
        
        return $this;
    }
    
    /**
     * Set viewname
     *
     * @param $viewname
     *
     * @return $this
     */
    public function setViewName($viewname)
    {
        $this->settings['viewname'] = $viewname;
        
        return $this;
    }
    
    /**
     * set PKs
     *
     * @param $pk
     *
     * @return $this
     */
    public function setPK($pk)
    {
        $this->settings['pk'] = $pk;
        
        return $this;
    }
    
    /**
     * Set fields
     *
     * @param $fields
     *
     * @return $this
     */
    public function setFields($fields)
    {
        $this->settings['fields'] = $fields;
        if (false === $this->settings['vfields']) {
            $this->setVFields($fields);
        }
        
        return $this;
    }
    
    /**
     * @param $arr
     *
     * @return $this
     */
    public function setVFields($arr)
    {
        $this->settings['vfields'] = $arr;
        
        return $this;
    }
    
    /**
     * Retourne la liste des champs
     * @return mixed
     */
    public function getVFields()
    {
        return $this->settings['vfields'];
    }
    
    /**
     * Retourne la liste des champs
     * @return mixed
     */
    public function getFields()
    {
        return $this->settings['fields'];
    }
    
    /**
     * Liste des champs à sélectionner ['id', 'count(id)'=> 'NB']
     *
     * @param array $arrayFields Si associatif la value sera le libellé
     *
     *
     * @return array indexed
     */
    public function setSelect($arrayFields = false)
    {
        if (is_array($arrayFields)) {
            $tbFields = [];
            
            /*
             * Parcours du tableau
             */
            foreach ($arrayFields as $key => $val) {
                // Clé indexé
                if (is_numeric($key)) {
                    $tbFields[] = $val;
                } else {
                    // Clé chaine
                    $tbFields[] = "$key";
                    
                    // Stocke le libellé pour affichage
                    $this->settings['libelle'][$key] = $val;
                }
            }
            $this->fieldsSelect = $tbFields;
        } else {
            $this->fieldsSelect = $this->getModelSettings('vfields', 'keys');
        }
        
        return $this->fieldsSelect;
    }
    
    /**
     * Retourne les paramètres du model
     *
     * @param bool        $name
     * @param string|null $type Type de données à récupérer (null: raw, keys, values)
     *
     * @return array|bool|string
     */
    public function getModelSettings($name = false, $type = null)
    {
        if ($name === false) {
            return $this->settings;
        }
        
        if ('keys' === $type) {
            return array_keys($this->settings[$name]);
        }
        
        if ('values' === $type) {
            return array_values($this->settings[$name]);
        }
        
        if (array_key_exists($name, $this->settings)) {
            return $this->settings[$name];
        } else {
            return false;
        }
    }
    
    /**
     * Ajoute une valeur au filtre
     *
     * @param $key
     * @param $val
     *
     * @return $this
     */
    public function addFilter($key, $val)
    {
        $this->filters[$key] = $val;
        
        return $this;
    }
    
    /**
     * Suppression
     *
     * @param array $aClauses
     *
     * @return bool
     * @throws Exception
     */
    public function delete($aClauses = [])
    {
        $this->newQB();
        
        $this->qb->delete($this->settings['tablename']);
        if (count($aClauses) == 0) {
            $this->setMessage("Erreur: Vous tentez de supprimer toutes les données", 10);
            throw new Exception($this->lastMessage->message, $this->lastMessage->code);
            
            return false;
        }
        
        $this->addFilters($aClauses);
        $this->buildFilters();
        
        return $this->execute();
    }
    
    /**
     * Ajoute un tableau de valeurs au filtre
     *
     * @param array $filters
     *
     * @return $this
     */
    public function addFilters($filters = [])
    {
        $this->filters = array_merge_recursive($this->filters, $filters);
        
        return $this;
    }
    
    /**
     * Application des filtres suivant les opérateurs
     *
     * Les filtres doivent faire partie des champs de la table
     * ou _expr_ pour une expression
     * sinon le champ est ignoré
     *
     * @return $this
     * @throws Exception
     */
    public function buildFilters()
    {
        /*
         * Récup du tableau de filtres
         */
        $filters = $this->getFilters();
        
        /*
         * Test les valeurs et champs
         */
        foreach ($filters as $k => $v) {
            $randId = $k . '_' . Helper::rand();
            
            $numericFields = false;
            
            /*
             * Ignore les valeurs vide des filtres de formulaires
             */
            if (is_string($v) && $v == '') {
                continue;
            }
            
            /*
             * Si c'est une expression
             */
            if (is_numeric($k)) {
                $numericFields = true;
                
                // Teste si la valeur est un champs unique
                if (!is_array($v)) {
                    $this->qb->andWhere("$v");
                    continue;
                } else {
                    // Sinon on construit la requête: $v = [ $fields => $val
                    $k = array_keys($v)[0];
                    $v = $v[$k];
                }
            }
            
            //            echo Helper::pre($this->settings);
            /*
             * Si le champs existe dans la table ou si c'est une expression
             */
            if ($numericFields
                || is_array($this->settings['vfields']) && array_key_exists($k, $this->settings['vfields'])
                || strpos($k, '_expr_') !== false) {
                // Test si $v est un opérateur de QBop
                $op = QBop::getOp($v);
                
                //Si c'est un opérateur UNAIRE (IS NULL, NOT NULL)
                if ($op !== false) {
                    switch ($v) {
                        case QBop::ISNULL:
                        case QBop::ISNOTNULL:
                            $this->qb->andWhere("$k $op");
                            break;
                    }
                } else {
                    if (strpos($k, '_expr_') !== false) {
                        $this->qb->andWhere($v);
                        $this->removeFilter($k);
                    } elseif (is_array($v)) {
                        // On test si la clé est un opérateur
                        // ou l'indice 0 du tableau (IN par défaut si pas d'operateur)
                        $qbOP = array_keys($v)[0];
                        
                        // Test si $qbOP est un opérateur de bdd sinon on force le IN
                        $op = QBop::getOp($qbOP);
                        
                        if ($op === false) {
                            $qbOP = QBop::IN;
                            $op = QBop::getOp($qbOP);
                        } else {
                            // On écrase la valeur par celle du tableau
                            $v = $v[$qbOP];
                        }
                        
                        /*
                         * Choix de l'operateur
                         */
                        switch ($qbOP) {
                            case QBop::EQ:
                            case QBop::GT:
                            case QBop::GE:
                            case QBop::LT:
                            case QBop::LE:
                            case QBop::NE:
                                $this->qb->andWhere("$k " . $op . " :$randId");
                                $this->qb->setParameter($randId, $v);
                                $this->removeFilter($k);
                                break;
                            
                            case QBop::LIKE:
                            case QBop::NOTLIKE:
                                $this->qb->andWhere("$k " . $op . " :$randId");
                                $this->qb->setParameter($randId, $v);
                                $this->removeFilter($k);
                                break;
                            
                            case QBop::IN:
                                // La valeur est un tableau
                                $this->qb->andWhere($this->qb->expr()->in($k, ":$randId"));
                                $this->qb->setParameter($randId, array_values($v), Connection::PARAM_STR_ARRAY);
                                $this->removeFilter($k);
                                break;
                            
                            case QBop::NOTIN:
                                // La valeur est un tableau
                                $this->qb->andWhere($this->qb->expr()->notIn($k, ":$randId"));
                                $this->qb->setParameter($randId, array_values($v), Connection::PARAM_STR_ARRAY);
                                $this->removeFilter($k);
                                break;
                            
                            case QBop::BETWEEN:
                            case QBop::NOTBETWEEN:
                                $this->qb->andWhere("$k " . $op . " :${randId}_first AND :${randId}_second");
                                $this->qb->setParameter("${randId}_first", $v[0]);
                                $this->qb->setParameter("${randId}_second", $v[1]);
                                $this->removeFilter($k);
                                break;
                        }
                    } else {
                        //Sinon on fait une égalité
                        $this->qb->andWhere("$k = :$randId");
                        $this->qb->setParameter($randId, $v);
                        $this->removeFilter($k);
                    }
                }
                $this->removeFilter($k);
            } else {
            }
        }
        
        return $this;
    }
    
    /**
     * Retourne la liste des filtres
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }
    
    /**
     * Initialise un tableau de valeurs au filtre
     *
     * @param array $filters
     *
     * @return $this
     */
    public function setFilters($filters = [])
    {
        $this->filters = $filters;
        
        return $this;
    }
    
    /**
     * Supprime un filtre
     *
     * @param $keys
     *
     * @return $this
     * @internal param array|string $key
     *
     */
    public function removeFilter($keys)
    {
        
        if (is_array($keys)) {
            foreach ($keys as $value) {
                $this->removeFilter($value);
            }
        } else {
            if (array_key_exists($keys, $this->filters)) {
                unset($this->filters[$keys]);
            }
        }
        
        return $this;
    }
    
    /**
     * Ecriture des données
     * @return integer|false de lignes modifiées
     * @throws Exception
     */
    public function execute()
    {
        try {
            return $this->qb->execute();
        } catch (DBALException $ex) {
            // Pas de Log pour l'écriture dans LOG sinon ça boucle !!!!!
            if (strtolower($this->settings['tablename']) != 'log') {
                $this->rollBack();
                $this->setMessage("Erreur d'écriture des données", 10, $this->debugSql());
            }
            
            throw new Exception($this->lastMessage->message, $this->lastMessage->code);
            
            return false;
        }
    }
    
    /**
     * RollBack
     * @return boolean|void
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function rollBack()
    {
        if ($this->conn->isTransactionActive()) {
            return $this->conn->rollBack();
        }
        
        return true;
    }
    
    /**
     * Return string debug
     * @return string
     */
    public function debugSql()
    {
        return Helper::pre($this->qb->getSql() . '<br>' . print_r($this->qb->getParameters(), 1));
    }
    
    /**
     * Retourne les lignes contenant un filtre sur la clé
     * Recherche dans le Rows
     *
     * @param  string $key
     * @param  mixed  $val      string, int, ...
     * @param bool    $multiple retourne 1 ou toutes les lignes
     *
     * @return array|bool False si erreur, sinon Row ou Rows si multiple=true
     */
    public function rowsFind($key, $val, $multiple = false)
    {
        $existsKeyNotChecked = true;
        $tbRes = [];
        $nbRows = $this->rowsCount();
        if ($nbRows == 0) {
            return false;
        }
        
        for ($i = 0; $i < $nbRows; $i++) {
            $row = $this->rows($i);
            
            /*
             * Test une fois si la clé à rechercher existe
             */
            if ($existsKeyNotChecked) {
                if (array_key_exists($key, $row)) {
                    $existsKeyNotChecked = false;
                } else {
                    return false;
                }
            }
            
            /*
             * Test si la valeur existe
             */
            if ($row[$key] == $val) {
                if ($multiple === false) {
                    $tbRes = $row;
                    break;
                } else {
                    $tbRes[] = $row;
                }
            }
        }
        
        return $tbRes;
    }
    
    /**
     * Retourne le nombre de ligne lues
     * @return int
     */
    public function rowsCount()
    {
        return count($this->rows);
    }
    
    /**
     * Retourne la liste des données ou une ligne
     *
     * @param null|integer $index
     *
     * @return array
     */
    public function rows($index = null)
    {
        if (is_numeric($index)) {
            if (isset($this->rows[$index])) {
                return $this->rows[$index];
            } else {
                return false;
            }
        }
        
        return $this->rows;
    }
    
    /**
     * Retourne le tableau de résulats par une autre clé
     *
     * @param $key
     *
     * @return array
     */
    public function indexedBy($key)
    {
        $res = [];
        
        $nbRows = $this->rowsCount();
        for ($i = 0; $i < $nbRows; $i++) {
            $row = $this->rows($i);
            $res[$row[$key]][] = $row;
        }
        
        return $res;
    }
    
    /**
     * Ajoute les champs calculés et test les valeurs
     *
     * @param array $adata
     *
     * @return $this
     * @throws Exception
     */
    public function addCalcFields(&$adata = [])
    {
        
        return $this;
    }
    
    /**
     * Create or Replace data
     *
     * @param array $aSet
     * @param array $aClauses
     *
     * @return bool|int (int: lastinsertid)
     * @throws Exception
     */
    public function createOrReplace($aSet = [], $aClauses = [])
    {
        $this->qb->resetQueryParts();
        $this->qb->from($this->getModelSettings('tablename'))
                 ->select($this->getModelSettings('fields', 'keys'));
        
        $this->setFilters($aClauses);
        $this->buildFilters();
        
        try {
            // Test d'existance
            if ($this->read() !== false && $this->rowsCount() == 0) {
                // Si pas trouvé, création
                $this->qb->resetQueryParts();
                
                return $this->create($aSet);
            } else {
                // Sinon mise à jour
                $this->qb->resetQueryParts();
                
                return $this->update($aSet, $aClauses);
            }
        } catch (Exception $ex) {
            $this->setMessage($ex->getMessage(), $ex->getCode());
            throw $ex;
            
            return false;
        }
    }
    
    /**
     * Ajout des options à la requête
     *
     * @param array $options [limit=>1, groupBy=>'....', order=>'....'
     *
     * @return DoctrineModelInterface
     */
    public function setOptions($options)
    {
        if (is_array($options)) {
            foreach ($options as $option => $value) {
                switch ($option) {
                    case 'firstResult':
                        $this->qb->setFirstResult($value);
                        break;
                    
                    case 'maxResults':
                        $this->qb->setMaxResults($value);
                        break;
                    
                    case 'limit':
                        $this->qb->setFirstResult(0)->setMaxResults($value);
                        break;
                    
                    case 'groupby':
                        if (false === $value) {
                            $this->qb->resetQueryPart('groupBy');
                            break;
                        }
                        $this->qb->groupBy($value);
                        break;
                    
                    case 'orderby':
                        if (false === $value) {
                            $this->qb->resetQueryPart('orderBy');
                            break;
                        }
                        $this->qb->orderBy($value);
                        break;
                }
            }
        }
        
        return $this;
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
        $this->qb->insert($this->settings['tablename']);
        
        try {
            $this->buildWriteFields($aSet, QueryBuilder::INSERT);
        } catch (Exception $ex) {
            $this->setMessage($ex->getMessage(), $ex->getCode());
            throw $ex;
            
            return false;
        }
        
        if ($this->execute()) {
            return $this->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Création SQL des champs à créer/modifier
     *
     * @param array $aSet Les champs doivent être dans la table $this->settings
     *
     * @param int   $type QueryBuilder::INSERT | QueryBuilder::UPDATE
     *
     * @return $this
     * @throws Exception
     */
    public function buildWriteFields($aSet = [], $type = QueryBuilder::INSERT)
    {
        
        if (!is_array($aSet)) {
            $this->setMessage("Pas de données à enregistrer: $type", 5);
            throw new Exception($this->lastMessage->message, $this->lastMessage->code);
            
            return false;
        }
        
        if (!in_array($type, [QueryBuilder::INSERT, QueryBuilder::UPDATE])) {
            $this->setMessage("Erreur sur le type de donnée: $type", 10);
            throw new Exception($this->lastMessage->message, $this->lastMessage->code);
            
            return false;
        }
        if ($type == QueryBuilder::INSERT) {
            $fctWrite = 'setValue';
        } elseif ($type == QueryBuilder::UPDATE) {
            $fctWrite = 'set';
        } else {
            $this->setMessage("Erreur sur le type de donnée: $type", 10);
            throw new Exception($this->lastMessage->message, $this->lastMessage->code);
            
            return false;
        }
        
        //    QueryBuilder::
        foreach ($aSet as $field => &$value) {
            if (array_key_exists($field, $this->settings['fields'])) {
                $type = ($this->settings['fields'][$field]);
                
                
                /*
                 * Si la valeur est un des autres champs de la table
                 */
                if (array_key_exists($value, $this->settings['fields'])) {
                    call_user_func([$this->qb, $fctWrite], $field, $value, $type);
                    continue;
                }
                
                /*
                 * Validation de la valeur par rapport au type attendu + transformations
                 */
                if (!self::valideValue($value, $type)) {
                    $this->setMessage("Une valeur est non conforme au type attendu ($type), merci de vérifier vos données.", 20);
                    throw new Exception($this->lastMessage->message, $this->lastMessage->code);
                    
                    return false;
                }
                
                if (is_null($value) || $value == '') {
                    $value = null;
                }
                
                /*
                 * Traitement spécifiques sur certains Type
                 */
                switch ($type) {
                    case Type::DATE:
                    case Type::DATETIME:
                        /*
                         * Analyse du type de date
                         */
                        call_user_func([$this->qb, $fctWrite], $field, ":$field");
                        if (is_null($value)) {
                            $this->qb->setParameter("$field", null, Type::DATETIME);
                        } else {
                            $this->qb->setParameter("$field", $value, Type::STRING);
                        }
                        break;
                    
                    default:
                        call_user_func([$this->qb, $fctWrite], $field, ":$field");
                        $this->qb->setParameter("$field", $value, $type);
                        break;
                }
            }
        }
        
        //        echo $this->debugSql(); die();
        return $this;
    }
    
    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }
    
    /**
     * Retourne le lastInsertID
     *
     * @param string|null $seqname
     *
     * @return mixed|false
     */
    public function lastInsertId($seqname = null)
    {
        $seqname = (is_null($seqname)) ? $this->getSequence() : $seqname;
        try {
            return $this->getConnection()->lastInsertId($seqname);
        } catch (Exception $ex) {
            return false;
        }
    }
    
    /**
     * Mise à jour
     *
     * @param array $aSet
     *
     * @param array $aClauses
     *
     * @return bool
     * @throws Exception
     */
    public function update($aSet = [], $aClauses = [])
    {
        $this->qb->update($this->settings['tablename']);
        
        
        $this->addFilters($aClauses);
        $this->buildFilters();
        
        try {
            $this->buildWriteFields($aSet, QueryBuilder::UPDATE);
        } catch (Exception $ex) {
            $this->setMessage($ex->getMessage(), $ex->getCode());
            throw new Exception($this->lastMessage->message, $this->lastMessage->code);
            
            return false;
        }
        
        return $this->execute();
    }
    
    /**
     * Retourne les données
     *
     * @param array      $aClauses
     * @param null|array $options [limit=>1, groupBy=>'....', order=>'....'
     *
     * @return array
     * @throws Exception
     */
    public function getAll($aClauses = [], $options = null)
    {
        $this->buildBase();
        $this->setFilters($aClauses);
        
        return $this->read($options);
    }
    
    public function getSelect()
    {
        return $this->fieldsSelect;
    }
    
    /**
     * Commence la transaction
     */
    public function beginTransaction()
    {
        $this->conn->beginTransaction();
    }
    
    /**
     * Commit
     */
    public function commit()
    {
        if ($this->conn->isTransactionActive()) {
            $this->conn->commit();
        }
    }
    
    /**
     * Retourne la PK de la table
     * @return mixed
     * @throws Exception
     */
    public function getPk()
    {
        
        if (!is_array($this->settings)) {
            $this->setMessage("PK inconnue pour la table " . $this->settings['tablename'], 10);
            throw new Exception($this->lastMessage->message, $this->lastMessage->code);
        }
        
        if (is_string($this->settings['pk'])) {
            return $this->settings['pk'];
        } else {
            return array_values($this->settings['pk'])[0];
        }
    }
    
    /**
     * Traduit une liste de code par son libelle
     *
     * @param array               $arrayKeys 0 indexed
     * @param DictionaryInterface $dictionary
     *
     * @return array
     */
    public function translate($arrayKeys = [], $dictionary)
    {
        $nbKeys = count($arrayKeys);
        // Transposition pour les libelles
        for ($i = 0; $i < $nbKeys; $i++) {
            $key = $arrayKeys[$i];
            
            $value = $dictionary->getString($key);
            
            $arrayKeys[$i] = ($value === false) ? ucwords($key) : $value;
        }
        
        return $arrayKeys;
    }
    
    /**
     * Set sequence name
     *
     * @param $seqname
     *
     * @return $this
     */
    public function setSequence($seqname)
    {
        $this->settings['sequence'] = $seqname;
        
        return $this;
    }
    
    /**
     * retourne la sequence
     * @return string|null
     */
    public function getSequence()
    {
        return (!empty($this->settings['sequence'])) ? $this->settings['sequence'] : null;
    }
    
    /**
     * set Last message
     *
     * @param      $message
     * @param int  $code
     * @param null $debug
     *
     * @return DoctrineModelAbstract
     */
    protected function setMessage($message, $code = 5, $debug = null)
    {
        $this->lastMessage->message = $message;
        $this->lastMessage->debug = $debug;
        $this->lastMessage->code = $code;
        
        return $this;
    }
    
    /**
     * Construction de la requete de lecture
     * @return $this
     */
    protected function buildBase()
    {
        $this->newQB();
        $this->qb->resetQueryParts();
        $this->qb->from($this->getModelSettings('viewname'));
        
        if ($this->getSelect() !== false) {
            $this->qb->select($this->getSelect());
        } else {
            $this->qb->select($this->getModelSettings('vfields', 'keys'));
        }
        
        return $this;
    }
    
    /**
     * Lecture des données
     *
     * @param null $options
     *
     * @return array
     * @throws Exception
     */
    private function read($options = null)
    {
        $this->buildFilters();
        
        if (is_array($options)) {
            $this->setOptions($options);
        }
        
        try {
            $res = $this->qb->execute();
            $this->rows = $res->fetchAll();
            
            return $this->rows;
        } catch (Exception $ex) {
            //            echo $this->debugSql();
            //            echo $ex->getMessage();
            $this->setMessage("Erreur de lecture des données", 100, $ex->getMessage() . "\n" . $this->debugSql());
            throw new Exception($this->lastMessage->message, $this->lastMessage->code);
        }
        
        return [];
    }
}
