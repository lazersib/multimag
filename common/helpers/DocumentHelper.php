<?php
/**
 * Created by PhpStorm.
 * User: dm
 * Date: 2/19/16
 * Time: 10:21 AM
 */

namespace common\helpers;
use InvalidArgumentException;

class DocumentHelper
{
    /**
     * Возвращает сумму дочерних документов у документа с id=doc_id
     *
     * @param $docId int номер документа в doc_list
     * @return float
     */
    public static function getCalculatedPaySum($docId) {
        if(!settype($docId, 'int'))
        {
            throw new InvalidArgumentException('$docId должен быть int');
        }
        global $db;
        $docs = array($docId);
        $sum = 0;

        while ($docs) {
            $cur_doc = array_pop($docs);
            $res = $db->query("SELECT `id`, `sum`, `type`, `ok` FROM `doc_list` WHERE `p_doc`=$cur_doc");

            while ($line = $res->fetch_assoc()) {
                $docs[] = $line['id'];
                if ($line['ok'] == 0 ||
                    $line['type'] != 4 && $line['type'] != 6) {
                    continue;
                }
                $sum += $line['sum'];
            }
        }
        return round($sum, 2);
    }

    /**
     * Ищет сохраненный в бд paysum и возвращает строку приводимую к float
     * При отсутствии записи возвращает false
     * @param $docId
     * @return false|string
     */
    public static function getSavedPaySum($docId)
    {
        if(!settype($docId, 'int'))
        {
            throw new InvalidArgumentException('$docId должен быть int');
        }
        global $db;
        $resource = $db->query("SELECT `value` FROM `doc_dopdata` WHERE `doc`=$docId AND `param`='paysum'");
        if($result = $resource->fetch_row())
        {
            return $result[0];
        }
        return false;
    }
}