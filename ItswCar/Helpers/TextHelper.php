<?php declare(strict_types=1);
/**
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 29.09.2021
 * Time: 09:26
 * File: TextHelper.php
 * @package ItswCar\Helpers
 */

namespace ItswCar\Helpers;

use ItswCar\Traits\LoggingTrait;

class TextHelper {
	use LoggingTrait;
	
	/**
	 * @param string $string
	 * @return array|string|string[]
	 */
	public function filterBadWords(string $string) {
		if ($badWords = Shopware()->Container()->get('itsw.helper.config')->getValue('bad_words', 'ItswCar')) {
			try {
				$badWords = json_decode($badWords, TRUE, 512, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_IGNORE);
				
				$search = $badWords['search']??NULL;
				$replace = $badWords['replace']??NULL;
				
				if (!$search || !$replace ||
					!is_array($search) || !is_array($replace) ||
					(count($search) !== count($replace))) {
					return $string;
				}
				
				foreach($search as $index => $searchWord) {
					$_searchWord = ltrim($searchWord, '!');
					$caseSensitiv = !(mb_strlen($_searchWord) !== mb_strlen($searchWord));
					$searchWord = $_searchWord;
					$_searchWord = ltrim($searchWord, '_');
					$prefix = (mb_strlen($_searchWord) !== mb_strlen($searchWord)) ? ' ' : '';
					$searchWord = $_searchWord;
					$_searchWord = rtrim($searchWord, '_');
					$suffix = (mb_strlen($_searchWord) !== mb_strlen($searchWord)) ? ' ' : '';
					$searchWord = $_searchWord;
					
					if ($caseSensitiv) {
						$string = str_replace($prefix.$searchWord.$suffix, $prefix.$replace[$index].$suffix, $string);
					} else {
						$string = str_ireplace($prefix.$searchWord.$suffix, $prefix.$replace[$index].$suffix, $string);
					}
				}
			} catch (\Exception $exception) {
				$this->error($exception);
				return $string;
			}
		} else {
			$this->error('could not read bad_words from config');
			return $string;
		}
		
		return $string;
	}
	
}