<?php

include(__DIR__.'/../CaptchaBuilderInterface.php');
include(__DIR__.'/../PhraseBuilderInterface.php');
include(__DIR__.'/../CaptchaBuilder.php');
include(__DIR__.'/../PhraseBuilder.php');

use Gregwar\Captcha\CaptchaBuilder;

header('Content-type: image/jpeg');

$builder = CaptchaBuilder::create()
    ->build();
$_SESSION['phrase'] = $builder->getPhrase();
 $builder->output()
;
