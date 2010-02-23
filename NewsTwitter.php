<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight webCMS
 * Copyright (C) 2005 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 *
 * PHP version 5
 * @copyright  Andreas Schempp 2009
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 * @version    $Id$
 */


class NewsTwitter extends Frontend
{
	/**
	 * Twitter when onsubmit_callback is triggered
	 */
	public function sendNow($dc)
	{
		$this->import('Database');
		
		$objNews = $this->Database->prepare("SELECT tl_news.*, tl_news_archive.twitterAuth, tl_news_archive.jumpTo AS parentJumpTo FROM tl_news LEFT OUTER JOIN tl_news_archive ON tl_news.pid=tl_news_archive.id WHERE tl_news_archive.twitter='1' AND tl_news.twitter='1' AND twitterStatus='now' AND published='1' AND tl_news.id=?")->limit(1)->execute($dc->id);
		
		if (!$objNews->numRows)
			return;
			
		$strUrl = '';
		if ($objNews->twitterUrl)
		{
			$strUrl = $this->generateNewsUrl($objNews);
		}
			
		if ($this->twitter($objNews->twitterAuth, (strlen($objNews->twitterMessage) ? $objNews->twitterMessage : (strlen($objNews->teaser) ? $objNews->teaser : strip_tags($objNews->text))), $strUrl))
		{
			$this->Database->prepare("UPDATE tl_news SET twitterStatus='sent' WHERE id=?")->execute($objNews->id);
		}
	}
	
	
	/**
	 * Run cron job and find news to twitter
	 */
	public function cron()
	{
		$this->import('Database');
		
		$objNews = $this->Database->prepare("SELECT tl_news.*, tl_news_archive.twitterAuth FROM tl_news LEFT OUTER JOIN tl_news_archive ON tl_news.pid=tl_news_archive.id WHERE tl_news_archive.twitter='1' AND tl_news.twitter='1' AND twitterStatus='cron' AND published='1'")->limit(1)->execute($dc->id);
		
		if (!$objNews->numRows)
			return;
			
		while( $objNews->next() )
		{
			// Check if news is withing start & stop date
			if (($objNews->start > 0 && $objNews->start > time()) || ($objNews->stop > 0 && $objNews->stop < time()))
				continue;
				
			$strUrl = '';
			if ($objNews->twitterUrl)
			{
				$strUrl = $this->generateNewsUrl($objNews);
			}
		
			if ($this->twitter($objNews->twitterAuth, (strlen($objNews->twitterMessage) ? $objNews->twitterMessage : (strlen($objNews->teaser) ? $objNews->teaser : strip_tags($objNews->text))), $strUrl))
			{
				$this->Database->prepare("UPDATE tl_news SET twitterStatus='sent' WHERE id=?")->execute($objNews->id);
			}
		}
	}
	
	
	/**
	 * Here the whole magic happens.
	 * It actually takes 4! lines of code to twitter!
	 */
	private function twitter($strAuth, $strStatus, $strUrl='')
	{
		// Shorten message
		if (strlen($strStatus) > 120)
		{
			$this->import('String');
            $strStatus = $this->String->substr($strStatus, 110) . ' ...';
        }
        
        if (strlen($strUrl))
        {
	        // Make sure url has protocol and domain
    	    if (substr($strUrl, 0, 4) != 'http')
        	{
        		$strUrl = $this->Environment->url . '/' . $strUrl;
	        }
        
    	    $strUrl = $this->shortUrl($strUrl);
		}
		
        $strData = 'source=typolight&status=' . urlencode($strStatus . ' ' . $strUrl);
        
        $objRequest = new Request();
        $objRequest->setHeader('Authorization', 'Basic {' . $strAuth . '}');
        $objRequest->send('http://twitter.com/statuses/update.json', $strData, 'POST');
        
        if ($objRequest->hasError())
        {
        	$this->log('Error posting to Twitter', 'NewsTwitter twitter()', TL_ERROR);
        	return false;
        }
        
        return true;
	}
	
	
	/**
	 * Short url using is.gd (http://is.gd/api_info.php)
	 */
	private function shortUrl($strUrl)
	{
		$objRequest = new Request();
		$objRequest->send('http://is.gd/api.php?longurl='.$strUrl);
		
		if ($objRequest->hasError())
			return $strUrl;
		
		return $objRequest->response;
	}
	
	
	/**
	 * Generate a URL and return it as string
	 */
	private function generateNewsUrl(Database_Result $objArticle, $blnAddArchive=false)
	{
		// Link to external page
		if ($objArticle->source == 'external')
		{
			$this->import('String');

			if (substr($objArticle->url, 0, 7) == 'mailto:')
			{
				$objArticle->url = 'mailto:' . $this->String->encodeEmail(substr($objArticle->url, 7));
			}

			return ampersand($objArticle->url);
		}

		// Link to internal page
		else
		{
			$strUrl = ampersand($this->Environment->request, ENCODE_AMPERSANDS);

			// Get target page
			$objPage = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
								 	  ->limit(1)
									  ->execute((($objArticle->source == 'default') ? $objArticle->parentJumpTo : $objArticle->jumpTo));

			if ($objPage->numRows)
			{
				// Link to newsreader
				if ($objArticle->source == 'default')
				{
					$strUrl = ampersand($this->generateFrontendUrl($objPage->fetchAssoc(), '/items/' . ((!$GLOBALS['TL_CONFIG']['disableAlias'] && strlen($objArticle->alias)) ? $objArticle->alias : $objArticle->id)));
				}

				// Link to internal page
				else
				{
					$strUrl = ampersand($this->generateFrontendUrl($objPage->fetchAssoc()));
				}
			}

			// Add the current archive parameter (news archive)
			if ($blnAddArchive && strlen($this->Input->get('month')))
			{
				$strUrl .= ($GLOBALS['TL_CONFIG']['disableAlias'] ? '&amp;' : '?') . 'month=' . $this->Input->get('month');
			}

			return $strUrl;
		}
	}
	
	
	/**
	 * load_callback for Twitter Auth text field
	 */
	public function loadAuth($varValue)
	{
		return '';
	}
	
	
	/**
	 * save_callback for Twitter Auth text field
	 */
	public function saveAuth($varValue)
	{
		$arrAuth = deserialize($varValue, true);
		
		if (!strlen($arrAuth[0]) || !strlen($arrAuth[1]))
			return '';
			
		return base64_encode($arrAuth[0] . ':' . $arrAuth[1]);
	}
	
	
	/**
	 * Show twitter options if enabled in archive
	 */
	public function injectField()
	{
		if ($this->Input->get('act') == 'edit')
		{
			$objArchive = $this->Database->prepare("SELECT tl_news_archive.twitter FROM tl_news LEFT OUTER JOIN tl_news_archive ON tl_news.pid=tl_news_archive.id WHERE tl_news.id=?")->execute($this->Input->get('id'));
			
			if ($objArchive->numRows && $objArchive->twitter)
			{
				$GLOBALS['TL_DCA']['tl_news']['palettes']['default'] = str_replace('addEnclosure;', 'addEnclosure;{twitter_legend},twitter;', $GLOBALS['TL_DCA']['tl_news']['palettes']['default']);
				$GLOBALS['TL_DCA']['tl_news']['palettes']['internal'] = str_replace('addEnclosure;', 'addEnclosure;{twitter_legend},twitter;', $GLOBALS['TL_DCA']['tl_news']['palettes']['internal']);
				$GLOBALS['TL_DCA']['tl_news']['palettes']['external'] = str_replace('addEnclosure;', 'addEnclosure;{twitter_legend},twitter;', $GLOBALS['TL_DCA']['tl_news']['palettes']['external']);
			}
		}
	}
}

