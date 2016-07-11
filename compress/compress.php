<?php
defined('_JEXEC') or die;

class PlgSystemCompress extends JPlugin
{
	private $_script;
	private $_scripts;
	private $_scriptsInc;
	

	public function onAfterRender(){

		$app = JFactory::getApplication();
		if($app->isAdmin()) return;

		$scripts = array();

		foreach ($this->_scripts as $src => $attr) {
		
			$src = explode('?', $src);
			$scripts[] = JFile::read(JPATH_BASE.$src[0]);
			
		}
		
		$scripts = implode(";\n", $scripts);
		
		$CRC = hash('crc32', $scripts);
			
		$file = '/cache/'.$CRC.'.js';
		
		if(!JFile::exists(JPATH_BASE.$file))
			JFile::write(JPATH_BASE.$file, $scripts);

        //Parse document
		$buffer = JResponse::getBody();
		
		$buffer = preg_replace('#(\<!--.*?--\>)+#si', '', $buffer);
		
		$regex  = '#<script.*?\>(.*?)\</script\>#si';
		$buffer = preg_replace_callback($regex, 'self::compile', $buffer);
		
		$scriptBlock = '<script type="text/javascript" src="'.$file.'"></script>';
		$scriptBlock .= $this->_scriptsInc;
		$scriptBlock .= '<script type="text/javascript">'.$this->_script.'</script>';
		$scriptBlock .= '</body>';
		
		$buffer = preg_replace('#\</body\>#', $scriptBlock, $buffer);
		
		JResponse::setBody($buffer);
		return true;
			
		
	}
	
	public function onBeforeCompileHead(){
		
		$app = JFactory::getApplication();
		if($app->isAdmin()) return;
		
		$doc = JFactory::getDocument();
		
		$this->_script = $doc->_script['text/javascript'];
		$this->_scripts = $doc->_scripts;
		
		unset($doc->_script['text/javascript']);
		$doc->_scripts = array();
		
	}
	
	private function compile(&$matches)
	{
        if(false !== strpos($matches[0], 'persist'))
            return $matches[0];

		if(!trim($matches[1])){
			if(false !== strpos($matches[0], 'async'))
				return $matches[0];
			$this->_scriptsInc .= $matches[0];
		}
		else{
            $this->_script .= ";\n".$matches[1];
		}
		
		return '';
	}


}