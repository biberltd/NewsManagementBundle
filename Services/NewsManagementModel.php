<?php

namespace BiberLtd\Bundle\NewsManagementBundle\Services;

/** Extends CoreModel */
use BiberLtd\Core\CoreModel;
/** Entities to be used */
use BiberLtd\Bundle\NewsManagementBundle\Entity as BundleEntity;
/** Helper Models */
use BiberLtd\Bundle\SiteManagementBundle\Services as SMMService;
use BiberLtd\Bundle\MultiLanguageSupportBundle\Services as MLSService;
/** Core Service */
use BiberLtd\Core\Services as CoreServices;

class NewsManagementModel extends CoreModel {

    public $by_opts = array('entity', 'id', 'code', 'url_key', 'post');
    public $entity = array(
            'news' => array('name' => 'NewsManagementBundle:News', 'alias' => 'n'),
            'news_localization' => array('name' => 'NewsManagementBundle:NewsLocalization', 'alias' => 'nl'),
            'news_category' => array('name' => 'NewsManagementBundle:NewsCategory', 'alias' => 'nc'),
            'news_category_localization' => array('name' => 'NewsManagementBundle:NewsCategoryLocalization', 'alias' => 'ncl'),
            'categories_of_news' => array('name' => 'NewsManagementBundle:CategoriesOfNews', 'alias' => 'con'),
            'files_of_news' => array('name' => 'NewsManagementBundle:FilesOfNews', 'alias' => 'fon'),
        );

    /**
     * @name 		deleteNewsItem()
     * Deletes an existing item from database.
     *
     * @since		1.0.0
     * @version         1.0.0
     * @author          Said İmamoğlu
     *
     * @use             $this->deleteNewsItems()
     *
     * @param           mixed           $form           Entity, id or url key of item
     * @param           string          $by
     *
     * @return          mixed           $response
     */
    public function deleteNewsItem($form, $by = 'entity') {
        return $this->deleteNewsItems(array($form), $by);
    }

    /**
     * @name            deleteNewsItems()
     * Deletes provided items from database.
     *
     * @since		1.0.0
     * @version         1.0.0
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           array           $collection     Collection of NewsItem entities, ids, or codes or url keys
     * @param           string          $by             Accepts the following options: entity, id, code, url_key
     *
     * @return          array           $response
     */
    public function deleteNewsItems($collection, $by = 'entity') {
        $this->resetResponse();
        $by_opts = array('entity', 'id', 'url_key');
        if (!in_array($by, $by_opts)) {
            return $this->createException('InvalidParameterValueException', 'err.invalid.parameter.collection', implode(',', $by_opts));
        }
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameterException', 'err.invalid.parameter.collection', 'Array');
        }
        $entries = array();
        /** Loop through items and collect values. */
        $delete_count = 0;
        foreach ($collection as $form) {
            $value = '';
            if (is_object($form)) {
                if (!$form instanceof BundleEntity\News) {
                    return $this->createException('InvalidParameterException', 'err.invalid.parameter.collection', 'BundleEntity\News');
                }
                $this->em->remove($form);
                $delete_count++;
            } else if (is_numeric($form) || is_string($form)) {
                $value = $form;
            } else {
                /** If array values are not numeric nor object */
                return $this->createException('InvalidParameterException', 'err.invalid.parameter.collection', 'integer, string, or Module entity');
            }
            if (!empty($value) && $value != '') {
                $entries[] = $value;
            }
        }
        /**
         * Control if there is any entity ids in collection.
         */
        if (count($entries) < 1) {
            return $this->createException('InvalidParameterException', 'err.invalid.parameter.collection', 'Array');
        }
        $join_needed = true;
        /**
         * Prepare query string.
         */
        switch ($by) {
            case 'entity':
                /** Flush to delete all persisting objects */
                $this->em->flush();
                /**
                 * Prepare & Return Response
                 */
                $this->response = array(
	    'rowCount' => $this->response['rowCount'],
                    'result' => array(
                        'set' => null,
                        'total_rows' => $delete_count,
                        'last_insert_id' => null,
                    ),
                    'error' => false,
                    'code' => 'scc.db.delete.done',
                );
                return $this->response;
            case 'id':
                $values = implode(',', $entries);
                break;
            /** Requires JOIN */
            case 'url_key':
                $join_needed = true;
                $values = implode('\',\'', $entries);
                $values = '\'' . $values . '\'';
                break;
        }
        if ($join_needed) {
            $q_str = 'DELETE ' . $this->entity['news']['alias']
                    . ' FROM ' . $this->entity['news_localization']['name'] . ' ' . $this->entity['news_localization']['alias']
                    . ' JOIN ' . $this->entity['news_localization']['name'] . ' ' . $this->entity['news_localization']['alias']
                    . ' WHERE ' . $this->entity['news_localization']['alias'] . '.' . $by . ' IN(:values)';
        } else {
            $q_str = 'DELETE ' . $this->entity['news']['alias']
                    . ' FROM ' . $this->entity['news']['name'] . ' ' . $this->entity['form']['alias']
                    . ' WHERE ' . $this->entity['news']['alias'] . '.' . $by . ' IN(:values)';
        }
        /**
         * Create query object.
         */
        $query = $this->em->createQuery($q_str);
        $query->setParameter('values', $entries);
        /**
         * Free memory.
         */
        unset($values);
        /**
         * 6. Run query
         */
        $query->getResult();
        /**
         * Prepare & Return Response
         */
        $this->response = array(
	    'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $entries,
                'total_rows' => count($entries),
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.delete.done',
        );
        return $this->response;
    }

    /**
     * @name            listNewsItems()
     * List items of a given collection.
     *
     * @since		1.0.0
     * @version         1.0.0
     * @author          Said Imamoglu
     *
     * @use             $this->resetResponse()
     * @use             $this->createException()
     * @use             $this->prepare_where()
     * @use             $this->createQuery()
     * @use             $this->getResult()
     * 
     * @throws          InvalidSortOrderException
     * @throws          InvalidLimitException
     * 
     *
     * @param           mixed           $filter                Multi dimensional array
     * @param           array           $sortorder              Array
     *                                                              'column'    => 'asc|desc'
     * @param           array           $limit
     *                                      start
     *                                      count
     * @param           string           $query_str             If a custom query string needs to be defined.
     *
     * @return          array           $response
     */
    public function listNewsItems($filter = null, $sortorder = null, $limit = null, $query_str = null) {
        $this->resetResponse();
        if (!is_array($sortorder) && !is_null($sortorder)) {
            return $this->createException('InvalidSortOrderException', '', 'err.invalid.parameter.sortorder');
        }

        /**
         * Add filter check to below to set join_needed to true
         */
        $order_str = '';
        $where_str = '';
        $group_str = '';
        $filter_str = '';


        /**
         * Start creating the query
         *
         * Note that if no custom select query is provided we will use the below query as a start
         */
        $localizable = true;
        if (is_null($query_str)) {
            if ($localizable) {
                $query_str = 'SELECT ' . $this->entity['news_localization']['alias'] .','. $this->entity['news']['alias']
                        . ' FROM ' . $this->entity['news_localization']['name'] . ' ' . $this->entity['news_localization']['alias']
                        . ' JOIN ' . $this->entity['news_localization']['alias'] . '.news ' . $this->entity['news']['alias'];
            } else {
                $query_str = 'SELECT ' . $this->entity['form']['alias']
                        . ' FROM ' . $this->entity['form']['name'] . ' ' . $this->entity['form']['alias'];
            }
        }
        /*
         * Prepare ORDER BY section of query
         */
        if (!is_null($sortorder)) {
            foreach ($sortorder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'name':
                    case 'url_key':
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        /*
         * Prepare WHERE section of query
         */

        if (!is_null($filter)) {
            $filter_str = $this->prepare_where($filter);
            $where_str = ' WHERE ' . $filter_str;
        }



        $query_str .= $where_str . $group_str . $order_str;


        $query = $this->em->createQuery($query_str);

        /*
         * Prepare LIMIT section of query
         */

        if (!is_null($limit) && is_numeric($limit)) {
            /*
             * if limit is set
             */
            if (isset($limit['start']) && isset($limit['count'])) {
                $query = $this->addLimit($query, $limit);
            } else {
                $this->createException('InvalidLimitException', '', 'err.invalid.limit');
            }
        }
        //print_r($query->getSql()); die;
        /*
         * Prepare and Return Response
         */
        //echo $query_str; die;
        $newsItems = $query->getResult();
        $newsCollection = array();
        foreach ($newsItems as $news) {
            $newsCollection[] = $news->getNews();
        }
        unset($newsItems);


        $total_rows = count($newsCollection);
        if ($total_rows < 1) {
            $this->response['error'] = true;
            $this->response['code'] = 'err.db.entry.notexist';
            return $this->response;
        }

        $this->response = array(
	    'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $newsCollection,
                'total_rows' => $total_rows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );

        return $this->response;
    }

    /**
     * @name 		getNewsItem()
     * Returns details of a gallery.
     *
     * @since		1.0.0
     * @version         1.0.0
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->listNewsItems()
     *
     * @param           mixed           $form               id, url_key
     * @param           string          $by                 entity, id, url_key
     *
     * @return          mixed           $response
     */
    public function getNewsItem($form, $by = 'id') {
        $this->resetResponse();
        $by_opts = array('id', 'url_key');
        if (!in_array($by, $by_opts)) {
            return $this->createException('InvalidParameterValueException', implode(',', $by_opts), 'err.invalid.parameter.by');
        }
        if (!is_object($form) && !is_numeric($form) && !is_string($form)) {
            return $this->createException('InvalidParameterException', 'NewsItem', 'err.invalid.parameter');
        }
        if (is_object($form)) {
            if (!$form instanceof BundleEntity\NewsItem) {
                return $this->createException('InvalidParameterException', 'NewsItem', 'err.invalid.parameter');
            }
            /**
             * Prepare & Return Response
             */
            $this->response = array(
	    'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $form,
                    'total_rows' => 1,
                    'last_insert_id' => null,
                ),
                'error' => false,
                'code' => 'scc.db.entry.exist',
            );
            return $this->response;
        }
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['news_localization']['alias'] . '.' . $by, 'comparison' => '=', 'value' => $form),
                )
            )
        );

        $response = $this->listNewsItems($filter, null, array('start' => 0, 'count' => 1));
        if ($response['error']) {
            return $response;
        }
        $collection = $response['result']['set'];
        /**
         * Prepare & Return Response
         */
        $this->response = array(
	    'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $collection[0],
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name 		doesNewsItemExist()
     * Checks if entry exists in database.
     *
     * @since		1.0.0
     * @version         1.0.0
     * @author          Said İmamoğlu
     *
     * @use             $this->getNewsItem()
     *
     * @param           mixed           $form           id, url_key
     * @param           string          $by             id, url_key
     *
     * @param           bool            $bypass         If set to true does not return response but only the result.
     *
     * @return          mixed           $response
     */
    public function doesNewsItemExist($form, $by = 'id', $bypass = false) {
        $this->resetResponse();
        $exist = false;

        $response = $this->getNewsItem($form, $by);

        if (!$response['error'] && $response['result']['total_rows'] > 0) {
            $exist = $response['result']['set'];
            $error = false;
        } else {
            $exist = false;
            $error = true;
        }

        if ($bypass) {
            return $exist;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
	    'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $exist,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => $error,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name 		insertNewsItem()
     * Inserts one or more item into database.
     *
     * @since		1.0.1
     * @version         1.0.3
     * @author          Said Imamoglu
     *
     * @use             $this->insertFiles()
     *
     * @param           array           $form        Collection of entities or post data.
     *
     * @return          array           $response
     */
    public function insertNewsItem($form, $by = 'post') {
        $this->resetResponse();
        return $this->insertNewsItems($form);
    }

    /**
     * @name            insertNewsItems()
     * Inserts one or more items into database.
     *
     * @since           1.0.1
     * @version         1.0.3
     * @author          Said Imamoglu
     *
     * @use             $this->createException()
     *
     * @throws          InvalidParameterException
     * @throws          InvalidMethodException
     *
     * @param           array           $collection        Collection of entities or post data.
     * @param           string          $by                entity, post
     *
     * @return          array           $response
     */
    public function insertNewsItems($collection, $by = 'post') {
        /* Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameterException', 'array() or Integer', 'err.invalid.parameter.collection');
        }

        if (!in_array($by, $this->by_opts)) {
            return $this->createException('InvalidParameterException', implode(',', $this->by_opts), 'err.invalid.parameter.by.collection');
        }

        if ($by == 'entity') {
            $sub_response = $this->insert_entities($collection, 'BiberLtd\\Core\\Bundles\\NewsManagementBundle\\Entity\\News');
            /**
             * If there are items that cannot be deleted in the collection then $sub_Response['process']
             * will be equal to continue and we need to continue process; otherwise we can return response.
             */
            if ($sub_response['process'] == 'stop') {
                $this->response = array(
	    'rowCount' => $this->response['rowCount'],
                    'result' => array(
                        'set' => $sub_response['entries']['valid'],
                        'total_rows' => $sub_response['item_count'],
                        'last_insert_id' => null,
                    ),
                    'error' => false,
                    'code' => 'scc.db.insert.done.',
                );

                return $this->response;
            } else {
                $collection = $sub_response['entries']['invalid'];
            }
        } elseif ($by == 'post') {

            $locCollection = array();
            foreach ($collection as $form) {
                $localizations = array();
                if (isset($form['localizations'])) {
                    $localizations = $form['localizations'];
                    unset($form['localizations']);
                }
                /** HANDLE FOREIGN DATA :: LOCALIZATIONS */
                if (count($localizations) > 0) {
                    $locCollection = $localizations;
                }
                $assayEntity = new BundleEntity\News();
                foreach ($form['entity'] as $column => $value) {
                    $formMethod = 'set' . $this->translateColumnName($column);
                    if (method_exists($assayEntity, $formMethod)) {
                        $assayEntity->itemMethod($value);
                    } else {
                        return $this->createException('InvalidMethodException', 'method not found in entity', 'err.method.notfound');
                    }
                    //$this->em->persist($assayEntity);
                }

                $this->insert_entities(array($assayEntity), 'BiberLtd\\Core\\Bundles\\NewsManagementBundle\\Entity\\News');

                $entityLocalizationCollection = array();
                foreach ($locCollection as $localization) {
                    if ($localization instanceof BundleEntity\AssayLocalization) {
                        $entityLocalizationCollection[] = $localization;
                    } else {
                        $localizationEntity = new BundleEntity\AssayLocalization;
                        $localizationEntity->setAssay($assayEntity);
                        foreach ($localization as $key => $value) {
                            $localizationMethod = 'set' . $this->translateColumnName($key);
                            switch ($key) {
                                case 'language':
                                    $MLSModel = new MLSService\MultiLanguageSupportModel($this->kernel, $this->db_connection, $this->orm);

                                    $response = $MLSModel->getLanguage($value, 'id');
                                    if ($response['error']) {
                                        new CoreExceptions\InvalidLanguageException($this->kernel, $value);
                                        break;
                                    }
                                    $language = $response['result']['set'];
                                    $localizationEntity->setLanguage($language);
                                    unset($response, $MLSModel);
                                    break;
                                default:
                                    if (method_exists($localizationEntity, $localizationMethod)) {
                                        $localizationEntity->localizationMethod($value);
                                    } else {
                                        return $this->createException('InvalidMethodException', 'method not found in entity', 'err.method.notfound');
                                    }
                                    break;
                            }
                            $entityLocalizationCollection[] = $localizationEntity;
                        }
                    }
                }
                //echo '<pre>'; print_r($entityLocalizationCollection); die;
                $this->insert_entities($entityLocalizationCollection, 'BiberLtd\\Core\\Bundles\\NewsManagementBundle\\Entity\\AssayLocalization');
                //$this->em->persist($localizationEntity);
            }
            unset($localizationEntity);

            $this->em->flush();

            $this->response = array(
	    'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $collection,
                    'total_rows' => count($collection),
                    'last_insert_id' => '', //LAST INSERT ID missing..
                ),
                'error' => false,
                'code' => 'scc.db.insert.done',
            );

            return $this->response;
        }
    }

    /*
     * @name            updateNewsItem()
     * Updates single item. The item must be either a post data (array) or an entity
     * 
     * @since           1.0.0
     * @version         1.0.0
     * @author          Said Imamoglu
     * 
     * @use             $this->resetResponse()
     * @use             $this->updateNewsItems()
     * 
     * @param           mixed   $form     Entity or Entity id of a folder
     * 
     * @return          array   $response
     * 
     */

    public function updateNewsItem($form) {
        $this->resetResponse();
        return $this->updateNewsItems(array($form));
    }

    /*
     * @name            updateNewsItems()
     * Updates one or more item details in database.
     * 
     * @since           1.0.0
     * @version         1.0.0
     * @author          Said Imamoglu
     * 
     * @use             $this->update_entities()
     * @use             $this->createException()
     * @use             $this->listNewsItems()
     * 
     * 
     * @throws          InvalidParameterException
     * 
     * @param           array   $collection     Collection of item's entities or array of entity details.
     * @param           array   $by             entity or post
     * 
     * @return          array   $response
     * 
     */

    public function updateNewsItems($collection, $by = 'post') {
        if ($by == 'entity') {
            $sub_response = $this->update_entities($collection, 'BundleEntity\Assay');
            /**
             * If there are items that cannot be deleted in the collection then $sub_Response['process']
             * will be equal to continue and we need to continue process; otherwise we can return response.
             */
            if ($sub_response['process'] == 'stop') {
                $this->response = array(
	    'rowCount' => $this->response['rowCount'],
                    'result' => array(
                        'set' => $sub_response['entries']['valid'],
                        'total_rows' => $sub_response['item_count'],
                        'last_insert_id' => null,
                    ),
                    'error' => false,
                    'code' => 'scc.db.delete.done',
                );
                return $this->response;
            } else {
                $collection = $sub_response['entries']['invalid'];
            }
        } elseif ($by == 'post') {
            if (!is_array($collection)) {
                return $this->createException('InvalidParameterException', 'expected an array', 'err.invalid.by');
            }

            $formsToUpdate = array();
            $formId = array();
            $count = 0;

            foreach ($collection as $form) {
                if (!isset($form['id'])) {
                    unset($collection[$count]);
                }
                $formId[] = $form['id'];
                $formsToUpdate[$form['id']] = $form;
                $count++;
            }
            $filter = array(
                array(
                    'glue' => 'and',
                    'condition' => array(
                        array(
                            'glue' => 'and',
                            'condition' => array('column' => $this->entity['assay']['alias'] . '.id', 'comparison' => 'in', 'value' => $formId),
                        )
                    )
                )
            );
            $response = $this->listNewsItems($filter);
            if ($response['error']) {
                return $this->createException('InvalidParameterException', 'Array', 'err.invalid.parameter.collection');
            }

            $entities = $response['result']['set'];

            foreach ($entities as $entity) {
                $formData = $formsToUpdate[$entity->getId()];
                foreach ($formData as $column => $value) {
                    $formMethodSet = 'set' .$this->translateColumnName($column);
                    $entity->itemMethodSet($value);
                }
                $this->em->persist($entity);
            }
            $this->em->flush();
        }
    }

}
