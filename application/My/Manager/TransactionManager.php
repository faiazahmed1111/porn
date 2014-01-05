<?php
namespace My\Manager;
use Doctrine\ORM\EntityManager;
use My\Factory\MyEntityManagerFactory;
use My\Illusion\aop_add_before;


class TransactionManager{
	
	/**
	 * 
	 * @var EntityManage
	 */
	private $_em;
	
	private function __construct(){
		$this->em = MyEntityManagerFactory::getEntityManager();
	}
	
	
	
	public static function start(){		
		$em = MyEntityManagerFactory::getEntityManager();	
		/* @var $jp \My\Illusion\AopJoinpoint */
		
		
		$before = function($jp) use($em) {
// 			echo "AOP:BEFORE";
			TransactionManager::$_stateData[++TransactionManager::$_stateIndex] = 1;
			$em->beginTransaction();
		};
		
		$after = function ($jp) use($em) {			
			
			if ($jp->getException ()!==null) {
// 				echo "AOP:EXCEPTION";
				TransactionManager::$_stateData [TransactionManager::$_stateIndex] = 2;
				$em->rollback();
			} else {
// 				echo "AOP:AFTER";
				if (TransactionManager::$_stateData [TransactionManager::$_stateIndex]===1) {
					$em->commit ();
				} else if(TransactionManager::$_stateData [TransactionManager::$_stateIndex]==0){
					$em->rollback();
				}
				TransactionManager::$_stateIndex--;
			}			
// 			
		};	
		
		$namespace='*Controller->*Action()';		
		aop_add_before($namespace , $before);
		aop_add_after($namespace, $after);
		
		
		$namespace='public Models\Services\*Service->*()';
		aop_add_before($namespace , $before);
		aop_add_after($namespace, $after);
		
// 		aop_add_after_throwing('IndexController->test()', $exception);
	}
	
	public static $_stateData = array();
	public static $_stateIndex = 0;
	
	public static function readOnly(){
		self::$_stateData[self::$_stateIndex] = 0;
	}
	
	
	
	
	
	
	
	
	/**
	 *
	 * @var AbstractRepository
	 */
	protected static $instance;
	
	final public static function getInstance()
	{
		$class = get_called_class();
		if(!static::$instance)
			static::$instance = new $class();
	
		return static::$instance;
	}
	
}