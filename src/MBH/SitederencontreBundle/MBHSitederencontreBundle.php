<?php

namespace MBH\SitederencontreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MBHSitederencontreBundle extends Bundle
{
	public function getParent()
  	{
    	return 'FOSUserBundle';
  	}
}
