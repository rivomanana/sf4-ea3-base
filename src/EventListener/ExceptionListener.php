<?php


namespace App\EventListener;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ExceptionListener
{

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        try {
            if ( (method_exists($exception, 'getContext') and 403 == $exception->getContext()->getStatusCode()) ){
                $response = new Response($this->twig->render('@Twig/Exception/error403.html.twig'), 403);
                $event->setResponse($response);
            }
            //$response = new Response($this->twig->render('@Twig/Exception/error403.html.twig'), 403);
        } catch (LoaderError $exception) {

        } catch (SyntaxError $exception){

        } catch ( RuntimeError $exception ){

        }

        // inspect the exception
        // do whatever else you want, logging, modify the response, etc, etc

    }
}