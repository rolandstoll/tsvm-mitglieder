<?php
/**
 * Created by PhpStorm.
 * User: rstol
 * Date: 18.02.2019
 * Time: 16:40
 */

namespace classes;

class antrag
{
    /**
     * antrag constructor.
     * @param   array $system json config
     * @param   array $config json config
     */
    public function __construct($system, $config)
    {
        session_start();

        $this->system = $system;

        if (isset($_SESSION['alter'])) {
            if ($_SESSION['alter'] < 6) {
                $this->config = $config['Kind'];
            } else if ($_SESSION['alter'] < 18) {
                $this->config = $config['Jugend'];
            } else {
                $this->config = $config['Erwachsener'];
            }
        } else {
            $this->config = $config['Erwachsener'];
        }

        $this->abteilungen = $config['abteilungen'];
        $this->gesamt = 0;
        $this->gesamtNext = 0;
        $this->beitrag = array();
        $this->extras = array();
        $this->secretKey = $this->system['recaptcha']['api_secret'];
    }

    /**
     *
     */
    public function index()
    {
        $template = 'antrag/step_1';
        $page_title = 'Neuantrag - Persönliche Daten';
        $header = array(
            'title' => 'Neuantrag - Persönliche Daten',
            'breadcrumb' => array(
                'Home' => 'http://www.tsvmoosach.de',
                'Mitgliedschaft' => '/',
                'Neuantrag' => '#'
            )
        );

        // page data
        \Flight::view()->set('head_title', $page_title);
        \Flight::view()->set('data', $_SESSION);

        // final render
        \Flight::render('main/header', $header, 'header_content');
        \Flight::render($template, array(), 'body_content');
        \Flight::render('main/footer', array(), 'footer_content');
        \Flight::render('main/layout');
    }

    /**
     *
     */
    public function post()
    {
        // post request to server
        $url =  'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($this->secretKey) .  '&response=' . urlencode(\Flight::request()->data->token);
        $response = file_get_contents($url);
        $responseKeys = json_decode($response,true);

        if($responseKeys["success"]) {
            foreach (\Flight::request()->data as $key => $val) {
                if ($key == 'datenschutz') {
                    $_SESSION[$key] = is_null(\Flight::request()->data->$key) ? false : true;
                } else {
                    $_SESSION[$key] = \Flight::request()->data->$key;
                }
            }

            \Flight::redirect('/antrag/2');
        }
    }

    /**
     *
     */
    public function index2()
    {
        $template = 'antrag/step_2';
        $page_title = 'Neuantrag - Auswahl Abteilungen';
        $header = array(
            'title' => 'Neuantrag - Auswahl Abteilungen',
            'breadcrumb' => array(
                'Home' => 'http://www.tsvmoosach.de',
                'Mitgliedschaft' => '/',
                'Neuantrag' => '#'
            )
        );

        // page data
        \Flight::view()->set('data', $_SESSION);
        \Flight::view()->set('head_title', $page_title);
        \Flight::view()->set('config', $this->config);
        \Flight::view()->set('abteilungen', $this->abteilungen);

        // hauptverein: default = checked
        if (!isset($_SESSION['abteilung']['hauptverein'])) {
            $_SESSION['abteilung']['hauptverein'] = true;
        }

        // final render
        \Flight::render('main/header', $header, 'header_content');
        \Flight::render($template, array(), 'body_content');
        \Flight::render('main/footer', array(), 'footer_content');
        \Flight::render('main/layout');
    }

    /**
     *
     */
    public function post2()
    {
        // post request to server
        $url =  'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($this->secretKey) .  '&response=' . urlencode(\Flight::request()->data->token);
        $response = file_get_contents($url);
        $responseKeys = json_decode($response,true);

        if($responseKeys["success"]) {
            unset($_SESSION['abteilung']);              // reset
            unset($_SESSION['zustimmung_fussball']);    // reset

            foreach (\Flight::request()->data as $key => $val) {
                if ($val == 'on') {
                    if ($key == 'zustimmung_fussball') {
                        $_SESSION[$key] = true;
                    } else {
                        $_SESSION['abteilung'][$key] = true;
                    }
                } else {
                    if (empty(\Flight::request()->data->$key)) {
                        unset($_SESSION[$key]);
                    } else {
                        $_SESSION[$key] = \Flight::request()->data->$key;
                    }
                }
            }

            \Flight::redirect('/antrag/3');
        }
    }

    /**
     *
     */
    public function index3()
    {
        session_start();

        $template = 'antrag/step_3';
        $page_title = 'Neuantrag - Bankverbindung';
        $header = array(
            'title' => 'Neuantrag - Bankverbindung',
            'breadcrumb' => array(
                'Home' => 'http://www.tsvmoosach.de',
                'Mitgliedschaft' => '/',
                'Neuantrag' => '#'
            )
        );

        $this->calculatePricesAbteilungen();

        // page data
        \Flight::view()->set('head_title', $page_title);
        \Flight::view()->set('beitrag', $this->beitrag);
        \Flight::view()->set('extras', $this->extras);
        \Flight::view()->set('gesamt', $this->gesamt);
        \Flight::view()->set('gesamtNext', $this->gesamtNext);
        \Flight::view()->set('data', $_SESSION);
        \Flight::view()->set('abteilungen', $this->abteilungen);

        // final render
        \Flight::render('main/header', $header, 'header_content');
        \Flight::render($template, array(), 'body_content');
        \Flight::render('main/footer', array(), 'footer_content');
        \Flight::render('main/layout');
    }

    /**
     *
     */
    public function post3()
    {
        // post request to server
        $url =  'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($this->secretKey) .  '&response=' . urlencode(\Flight::request()->data->token);
        $response = file_get_contents($url);
        $responseKeys = json_decode($response,true);

        if($responseKeys["success"]) {
            unset($_SESSION['zustimmung']);    // reset

            foreach (\Flight::request()->data as $key => $val) {
                if ($key == 'zustimmung') {
                    $_SESSION[$key] = is_null(\Flight::request()->data->$key) ? false : true;
                } else {
                    if (empty(\Flight::request()->data->$key)) {
                        unset($_SESSION[$key]);
                    } else {
                        if (in_array($key, array('mandats_referenznummer'))) {
                            $_SESSION[$key] = str_replace(' ', '', \Flight::request()->data->$key);
                        } else if (in_array($key, array('konto_iban', 'konto_bic'))) {
                            $_SESSION[$key] =  strtoupper(\Flight::request()->data->$key);
                        } else {
                            $_SESSION[$key] = \Flight::request()->data->$key;
                        }
                    }
                }
            }

            \Flight::redirect('/antrag/4');
        }
    }

    /**
     *
     */
    public function index4()
    {
        $template = 'antrag/step_4';
        $page_title = 'Neuantrag - Zusammenfassung';
        $header = array(
            'title' => 'Neuantrag - Zusammenfassung',
            'breadcrumb' => array(
                'Home' => 'http://www.tsvmoosach.de',
                'Mitgliedschaft' => '/',
                'Neuantrag' => '#'
            )
        );

        $this->calculatePricesAbteilungen();

        // page data
        \Flight::view()->set('head_title', $page_title);
        \Flight::view()->set('beitrag', $this->beitrag);
        \Flight::view()->set('extras', $this->extras);
        \Flight::view()->set('gesamt', $this->gesamt);
        \Flight::view()->set('gesamtNext', $this->gesamtNext);
        \Flight::view()->set('data', $_SESSION);
        \Flight::view()->set('abteilungen', $this->abteilungen);

        // final render
        \Flight::render('main/header', $header, 'header_content');
        \Flight::render($template, array(), 'body_content');
        \Flight::render('main/footer', array(), 'footer_content');
        \Flight::render('main/layout');
    }

    /**
     *
     */
    public function post4()
    {
        // post request to server
        $url =  'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($this->secretKey) .  '&response=' . urlencode(\Flight::request()->data->token);
        $response = file_get_contents($url);
        $responseKeys = json_decode($response,true);

        $data = $_SESSION;
        $data['server'] = 'http://'. $_SERVER['HTTP_HOST']; //TODO: change to https!!!
        $data['hash'] = md5(bin2hex(random_bytes(32)));


        if($responseKeys["success"]) {

            // send mail to customer
            // mailer::sendValidation($data['email'], $data); //TODO: un-comment this!

            // store data in db
            $db = \Flight::db();
            foreach ($data['abteilung'] as $key => $value) {
                $data['abteilung'][$key] = 'pending';
            }
            $db->createAntrag($data);

            \Flight::redirect('/antrag/abschluss');
        }

        // kill the session
        session_destroy();
    }

    /**
     *
     */
    public function abschluss()
    {
        $template = 'antrag/abschluss';
        $page_title = 'Neuantrag - Abschluss';
        $header = array(
            'title' => 'Neuantrag - Abschluss',
            'breadcrumb' => array(
                'Home' => 'http://www.tsvmoosach.de',
                'Mitgliedschaft' => '/',
                'Neuantrag' => '#'
            )
        );

        // page data
        \Flight::view()->set('head_title', $page_title);

        // final render
        \Flight::render('main/header', $header, 'header_content');
        \Flight::render($template, array(), 'body_content');
        \Flight::render('main/footer', array(), 'footer_content');
        \Flight::render('main/layout');
    }

    /**
     *
     */
    public function bestaetigung()
    {
        $template = 'antrag/verifizierung';
        $page_title = 'Neuantrag - Verifizierung';
        $header = array(
            'title' => 'Neuantrag - Verifizierung',
            'breadcrumb' => array(
                'Home' => 'http://www.tsvmoosach.de',
                'Mitgliedschaft' => '/',
                'Neuantrag' => '#'
            )
        );

        // page data
        \Flight::view()->set('head_title', $page_title);

        // final render
        \Flight::render('main/header', $header, 'header_content');
        \Flight::render($template, array(), 'body_content');
        \Flight::render('main/footer', array(), 'footer_content');
        \Flight::render('main/layout');
    }

    /**
     *
     */
    public function calculatePricesAbteilungen()
    {
        $this->beitrag = 0;
        $this->gesamt = 0;
        $this->gesamtNext = 0;
        $this->extras = 0;

        $this->beitrag = array();
        $this->extras = array();
        foreach ($_SESSION['abteilung'] as $key => $value) {

            $title = $this->abteilungen[$key];
            switch ($key) {
                case 'fussball':
                    $this->beitrag[$key] = $this->config[$title]['Beitrag'][$_SESSION['eintrittsdatum']] + $this->config['Fußball']['Aufnahmegebühr'] + $this->config['Fußball']['Passantrag'][$_SESSION['passantrag']];
                    if ($_SESSION['eintrittsdatum'] == 'Passiv') {
                        $this->gesamtNext += $this->config[$title]['Beitrag']['Passiv'] + $this->config['Fußball']['Aufnahmegebühr'] + $this->config['Fußball']['Passantrag'][$_SESSION['passantrag']];
                    } else {
                        $this->gesamtNext += $this->config[$title]['Beitrag'][1] + $this->config['Fußball']['Aufnahmegebühr'] + $this->config['Fußball']['Passantrag'][$_SESSION['passantrag']];
                    }
                    $this->extras[$key] = 'lfd. Jahr inkl. Aufnahmegebühr + Passantrag';
                    break;
                case 'tennis':
                    $this->beitrag[$key] = $this->config[$title]['Beitrag'][$_SESSION['tennisTarif']];
                    $this->gesamtNext += $this->config[$title]['Beitrag'][$_SESSION['tennisTarif']];
                    break;
                default:
                    $this->beitrag[$key] = $this->config[$title]['Beitrag'];
                    $this->gesamtNext += $this->config[$title]['Beitrag'];
                    if ($this->config[$title]['Aufnahmegebühr']) {
                        $this->beitrag[$key] += $this->config[$title]['Aufnahmegebühr'];
                        $this->gesamtNext += $this->config[$title]['Aufnahmegebühr'];
                        $this->extras[$key] = 'inkl. Aufnahmegebühr';
                    }
                    break;
            }

            $this->gesamt += $this->beitrag[$key];
        }
    }
}