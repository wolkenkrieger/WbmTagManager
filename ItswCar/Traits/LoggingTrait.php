<?php declare(strict_types=1);
/**
 * Autor:    Rico Wunglück <development@itsw.dev>
 * Datum:    14.09.2021
 * Zeit:    09:56
 * Datei:    LoggingTrait.php
 */

namespace ItswCar\Traits;

use ItswCar\ItswCar;
use Shopware\Components\Logger;

trait LoggingTrait {
	
	/**
	 * @return int
	 */
	public function _getLogLevel(): int {
		if(empty(ItswCar::$logLevel)){
			ItswCar::$logLevel = Logger::WARNING;
		}
		return ItswCar::$logLevel;
	}
	
	/**
	 *
	 */
	public function _initLogger(): void {
		if(empty(ItswCar::$logger)){
			ItswCar::$logger = Shopware()->Container()->get('itswcar.service.debug_logger');
		}
	}
	
	/**
	 *
	 */
	public function _initWarningLogger(): void {
		if(empty(ItswCar::$warningLogger)){
			ItswCar::$warningLogger = Shopware()->Container()->get('itswcar.service.error_logger');
		}
	}
	
	/**
	 * @param       $msg
	 * @param array $context
	 */
	public function debug($msg, array $context = []): void {
		if($this->_getLogLevel() > Logger::DEBUG){
			return;
		}
		
		if ($msg instanceof \Exception) {
			if (empty($context)) {
				$context = [
					'code' => $msg->getCode(),
					'file' => $msg->getFile(),
					'line' => $msg->getLine(),
					'trace' => $msg->getTraceAsString()
				];
			}
			$msg = $msg->getMessage();
		}
		
		$this->_initLogger();
		ItswCar::$logger->debug($msg, $context);
	}
	
	/**
	 * @param       $msg
	 * @param array $context
	 */
	public function warning($msg, array $context = []): void {
		if($this->_getLogLevel() > Logger::WARNING){
			return;
		}
		
		if ($msg instanceof \Exception) {
			if (empty($context)) {
				$context = [
					'code' => $msg->getCode(),
					'file' => $msg->getFile(),
					'line' => $msg->getLine(),
					'trace' => $msg->getTraceAsString()
				];
			}
			$msg = $msg->getMessage();
		}
		
		$this->_initWarningLogger();
		ItswCar::$warningLogger->warning('⚠ '.$msg, $context);
		if($this->_getLogLevel() === Logger::DEBUG){
			$this->debug('⚠ '.$msg, $context);
		}
	}
	
	/**
	 * @param       $msg
	 * @param array $context
	 */
	public function error($msg, array $context = []): void {
		if($this->_getLogLevel() > Logger::ERROR){
			return;
		}
		
		if ($msg instanceof \Exception) {
			if (empty($context)) {
				$context = [
					'code' => $msg->getCode(),
					'file' => $msg->getFile(),
					'line' => $msg->getLine(),
					'trace' => $msg->getTraceAsString()
				];
			}
			$msg = $msg->getMessage();
		}
		
		$this->_initWarningLogger();
		ItswCar::$warningLogger->error('💣 '.$msg, $context);
		if($this->_getLogLevel() === Logger::DEBUG){
			$this->debug('💣 '.$msg, $context);
		}
	}
}