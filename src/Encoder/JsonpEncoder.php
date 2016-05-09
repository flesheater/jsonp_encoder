<?php

/**
 * @file
 * Contains \Drupal\jsonp_encoder\Encoder\JsonpEncoder.
 */

namespace Drupal\jsonp_encoder\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder as BaseJsonEncoder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Adds 'ajax to the supported content types of the JSON encoder'
 */
class JsonpEncoder extends BaseJsonEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The formats that this Encoder supports.
   *
   * @var array
   */
  protected static $format = array('jsonp');

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = array()) {

    $request = Request::createFromGlobals();
    if (!empty($request->query->get('callback')) && $this->isValidcallback($request->query->get('callback'))) {
      $callback = $request->query->get('callback');
    }
    else {
      $callback = 'callback';
    }

    $jsonp_responce = $this->encodingImpl->encode($data, $format, $context);
    
    // we are creating our own responce
    $response = new Response();
    $response->setContent($callback . '(' . $jsonp_responce . ')');
    $response->headers->set('Content-type', 'application/javascript');
    
    return $response->send();
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return in_array($format, static::$format);
  }

  /**
   * A simple validation function for the callback name
   */
  private function isValidcallback($callback) {

    $identifier_syntax
      = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';

    $reserved_words = array('break', 'do', 'instanceof', 'typeof', 'case',
      'else', 'new', 'var', 'catch', 'finally', 'return', 'void', 'continue', 
      'for', 'switch', 'while', 'debugger', 'function', 'this', 'with', 
      'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum', 
      'extends', 'super', 'const', 'export', 'import', 'implements', 'let', 
      'private', 'public', 'yield', 'interface', 'package', 'protected', 
      'static', 'null', 'true', 'false');

    return preg_match($identifier_syntax, $callback)
        && ! in_array(mb_strtolower($callback, 'UTF-8'), $reserved_words);
}

}
