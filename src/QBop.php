<?php
/**
 * Copyright (c) vdeApps 2018
 */

/**
 * QBop: Query operator
 * @author vdeapps
 *
 */

namespace vdeApps\phpCore\DoctrineModel;

class QBop
{
    /**
     * OPERATOR
     * @var string
     */
    const EQ = '##OP#EQ##';
    const LT = '##OP#LT##';
    const LE = '##OP#LE##';
    const GT = '##OP#GT##';
    const GE = '##OP#GE##';
    const NE = '##OP#NE##';
    const IN = '##OP#IN##';
    const NOTIN = '##OP#NOTIN##';
    const ISNULL = '##OP#ISNULL##';
    const ISNOTNULL = '##OP#ISNOTNULL##';
    const LIKE = '##OP#LIKE##';
    const NOTLIKE = '##OP#NOTLIKE##';
    const BETWEEN = '##OP#BETWEEN##';
    const NOTBETWEEN = '##OP#NOTBETWEEN##';
    
    
    /**
     * JOINTURES
     * @var string
     */
    const UNION = '##Q#UNION##';
    const UNIONALL = '##Q#UNIONALL##';
    
    /**
     *
     * @var string
     */
    const IGNORE = '##INSERT#IGNORE##';
    const DUPLICATEKEY = '##INSERT#OR#UPDATE##';
    
    /**
     * Return the translate operator
     *
     * @param QBop ::<const> $operator
     *
     * @return bool|string
     */
    public static function getOp($operator)
    {
        if (!is_string($operator)) {
            return false;
        }
        
        switch ((string)$operator) {
            case self::EQ:
                return '=';
                break;
            
            case self::LT:
                return '<';
                break;
            
            case self::LE:
                return '<=';
                break;
            
            case self::GT:
                return '>';
                break;
            
            case self::GE:
                return '>=';
                break;
            
            case self::NE:
                return '<>';
                break;
            
            case self::IN:
                return ' IN ';
                break;
            
            case self::NOTIN:
                return ' NOT IN ';
                break;
            
            case self::ISNULL:
                return ' IS NULL';
                break;
            
            case self::ISNOTNULL:
                return ' IS NOT NULL';
                break;
            
            case self::LIKE:
                return ' LIKE ';
                break;
            
            case self::NOTLIKE:
                return ' NOT LIKE ';
                break;
            
            case self::BETWEEN:
                return ' BETWEEN ';
                break;
            
            case self::NOTBETWEEN:
                return ' NOT BETWEEN ';
                break;
            
            case self::UNION:
                return ' UNION ';
                break;
            
            case self::UNIONALL:
                return ' UNION ALL ';
                break;
            
            default:
                return false;
                break;
        }
    }
}
