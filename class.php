<?php
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;


if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class ExampleCompSimple extends CBitrixComponent {
   /**
	 * элементы каталога
	 * @var array()
	 */
    protected $elementList = [];

    /**
	 * кешируемые ключи arResult
	 * @var array()
	 */
	protected $cacheKeys = [];
	
	/**
	 * дополнительные параметры, от которых должен зависеть кеш
	 * @var array
	 */
	protected $cacheAddon = [];

    /**
     * Проверка наличия модулей требуемых для работы компонента
     * @return bool
     * @throws Exception
     */
    private function _checkModules() {
        if (   !Loader::includeModule('iblock')
            || !Loader::includeModule('sale')
        ) {
            throw new \Exception('Не загружены модули необходимые для работы модуля');
        }

        return true;
    }

    /**
     * подготавливает входные параметры
     * @param array $arParams
     * @return array
     */
    public function onPrepareComponentParams($params)
    {
        $result = array(
            'IBLOCK_TYPE' => trim($params['IBLOCK_TYPE']),
            'IBLOCK_ID' => intval($params['IBLOCK_ID']),
            'IBLOCK_CODE' => trim($params['IBLOCK_CODE']),            
            'COUNT_ELEMENTS' => intval($params['COUNT_ELEMENTS']) > 0 ? intval($params['COUNT_ELEMENTS']) : 10,            
            'CACHE_TIME' => intval($params['CACHE_TIME']) > 0 ? intval($params['CACHE_TIME']) : 3600,		
            
        );
        return $result;
    }

     /**
     * Возвращает список товаров    
     * @return elementList|array
     */
    protected function getElements() {
        $arSelect = Array("ID", "NAME", "CATALOG_PRICE_1");
        $arFilter = Array("IBLOCK_ID"=>IntVal($this->arParams['IBLOCK_ID']), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");           
        $res = CIBlockElement::GetList(Array(), $arFilter, false,  false, $arSelect);
        
        while($ob = $res->GetNextElement())
        {   
            $result["ID"] = $ob->GetFields()['ID'];
            $result["NAME"] = $ob->GetFields()['NAME'];
            $result["PRICE"] = $ob->GetFields()['CATALOG_PRICE_1'];
            $result["CURRENCY"] = $ob->GetFields()['CATALOG_CURRENCY_1']; 

            array_push($this->elementList, $result);      
        }
           
        return $this->elementList;
    }   
    
     /**
     * Сортирует элементы по цене 
     * Получает указаное количество товаров   
     * @return items|array
     */
    protected function sortElements() {
        $items = $this->getElements();
        usort($items, function($a, $b){
            return -($a["PRICE"] - $b["PRICE"]);
        });
        $items = array_slice($items, 0, $this->arParams['COUNT_ELEMENTS']);
        return $items;
    }

    /**
	 * определяет читать данные из кеша или нет
	 * @return bool
	 */
	protected function readDataFromCache()
	{
		global $USER;
		if ($this->arParams['CACHE_TYPE'] == 'N')
			return false;

		if (is_array($this->cacheAddon))
			$this->cacheAddon[] = $USER->GetUserGroupArray();
		else
			$this->cacheAddon = array($USER->GetUserGroupArray());

		return !($this->startResultCache(false, $this->cacheAddon, md5(serialize($this->arParams))));
	}

	/**
	 * кеширует ключи массива arResult
	 */
	protected function putDataToCache()
	{
		if (is_array($this->cacheKeys) && sizeof($this->cacheKeys) > 0)
		{
			$this->SetResultCacheKeys($this->cacheKeys);
		}
	}

	/**
	 * прерывает кеширование
	 */
	protected function abortDataCache()
	{
		$this->AbortResultCache();
	}

    /**
     * завершает кеширование
     * @return bool
     */
    protected function endCache()
    {
        if ($this->arParams['CACHE_TYPE'] == 'N')
            return false;

        $this->endResultCache();
    }

   
    /**
	 * получение результатов
	 */
    protected function getResult() {
        $this->arResult['ITEMS'] = $this->sortElements();  
        
    }
   
    public function executeComponent() {
        
		try
		{
            $this->_checkModules();
            $this->onPrepareComponentParams($this->arParams);            

			if (!$this->readDataFromCache())
			{			   
				$this->putDataToCache();
				$this->getResult();
			}

            $this->includeComponentTemplate();
			
		}
		catch (Exception $e)
		{
			$this->abortDataCache();
			ShowError($e->getMessage());
		}
    }
}