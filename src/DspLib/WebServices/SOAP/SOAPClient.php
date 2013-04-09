<?php

namespace DspLib\WebServices\SOAP;

/**
 * Classe d'invocation de WebServices SOAP
 *
 * @author Pierre Feyssaguet <pfeyssaguet@gmail.com>
 */

class SOAPClient
{

    private static $bDebug = false;

    /**
     * Options par défaut à passer au SoapClient
     *
     * @var array
     */
    private static $aDefaultOptions = array(
        'soap_version' => SOAP_1_2,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        'encoding' => SOAP_LITERAL,
        'style' => SOAP_RPC
    );

    /**
     * URL du WSDL
     *
     * @var string
     */
    private $sWSDL = null;

    /**
     * Options à passer au SoapClient
     *
     * @var array
     */
    private $aOptions = array();

    /**
     * Instance de SoapClient pour communiquer avec un serveur SOAP
     *
     * @var SoapClient
     */
    private $oSoapClient = null;

    /**
     * Active ou désactive le mode debug
     *
     * @param boolean $bDebug TRUE pour activer le mode debug
     *
     * @return void
     */
    public static function setDebug($bDebug)
    {
        self::$bDebug = $bDebug;
        self::$aDefaultOptions['trace'] = self::$bDebug;
    }

    /**
     * Constructeur
     *
     * @param string $sWSDL URL du WSDL
     * @param array $aOptions Options supplémentaires
     */
    public function __construct($sWSDL, array $aOptions = array())
    {
        ini_set('soap.wsdl_cache_enabled', 0);
        $this->sWSDL = $sWSDL;
        $this->aOptions = array_merge(self::$aDefaultOptions, $aOptions);
        $this->oSoapClient = new \SoapClient($this->sWSDL, $this->aOptions);
    }

    /**
     * Appelle un WebService
     *
     * @param string $sServiceName Nom du WebService
     * @param mixed $aArgs Arguments (array ou string s'il n'y a qu'un argument)
     *
     * @return mixed
     *
     * @throws WebServiceException En cas d'erreur
     */
    public function callService($sFunctionName, $aArgs = array())
    {
        // Si on a qu'un argument, on le met dans un tableau
        if (!is_array($aArgs)) {
            $aArgs = array($aArgs);
        }

        try {
            // On appelle le WebService et on renvoie le résultat
            // if debug mode is activated, print the function and args
            if (self::$bDebug) {
                echo '<p>Calling ' . $sFunctionName . '()...</p>';
                echo '<p>Arguments :</p>';
                echo '<pre>';
                var_dump($aArgs);
                echo '</pre>';
            }

            // perform the effective SOAP call
            $mReturn = $this->oSoapClient->__soapCall($sFunctionName, $aArgs);

            // if debug is activated, dump the response
            if (self::$bDebug) {
                echo '<p>Return value :</p>';
                echo '<pre>';
                var_dump($mReturn);
                echo '</pre>';
                echo $this->getTrace();
            }

            // On renvoie le résultat
            return $mReturn;

        } catch (\SoapFault $e) {
            if (self::$bDebug) {
                echo '<div class="error">';
                echo '<h3>Error !</h3>';
                echo '<p>SoapFault : [' . $e->faultcode . '] ' . $e->getMessage() . '</p>';
                echo '<p>Stack trace :</p>';
                echo '<pre>';
                echo $e->getTraceAsString();
                echo '</pre>';
                echo '</div>';

                echo $this->getTrace();
            }
            throw new SOAPException($e);
        } catch (\Exception $e) {
            throw new SOAPException($e);
        }
    }

    /**
     * Renvoie la trace du last request et last response (si le mode trace est activé)
     *
     * @return string Formatted trace
     */
    public function getTrace()
    {
        if (!self::$bDebug) {
            return 'Trace mode is not activated';
        }

        // Get the last request and response
        $sLastRequest = $this->oSoapClient->__getLastRequest();
        $sLastResponse = $this->oSoapClient->__getLastResponse();

        // Format the code
        $sLastRequest = \DspLib\DSPXMLCodeBeautifier::formatCode($sLastRequest);
        $sLastResponse = \DspLib\DSPXMLCodeBeautifier::formatCode($sLastResponse);

        $str = '<p>Last Request : </p>';
        $str .= '<div>' . $sLastRequest . '</div>';

        $str .= '<p>Last Response : </p>';
        $str .= '<div>' . $sLastResponse . '</div>';

        return $str;
    }
}
