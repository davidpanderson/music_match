<?php

// Sample BOINC project web config file.
// Modify it to suit your project.
// Put your version in html/project/project.inc
//
// add tra() around visible strings to make them translatable
// see inc/translation.inc and
// https://boinc.berkeley.edu/trac/wiki/TranslateProject for details

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

//-------------- Project name and owner

define("PROJECT", "Music Match");
define("COPYRIGHT_HOLDER", "David P. Anderson");

//-------------- URLs and directories

$master_url = parse_config(get_config(), "<master_url>");
define("URL_BASE", $master_url);
define("IMAGE_PATH", "../user_profile/images/");
define("IMAGE_URL", "user_profile/images/");
define("PROFILE_PATH", "../user_profile/");
define("PROFILE_URL", "user_profile/");
define("LANGUAGE_FILE", "languages.txt");

define('NO_COMPUTING', true);
define('DISABLE_TEAMS', true);
define('POSTAL_CODE', true);
//define('USER_HOME', "home.php");
define('SHORTCUT_ICON', "icon.jpg");
define('NEWS_FORUM_NAME', "Music Match news");

//-------------- contact info

define("SYS_ADMIN_EMAIL", "davea@berkeley.edu");
define("POST_REPORT_EMAILS", "davea@berkeley.edu");
    // Email addresses separated by pipe ( | ) that will receive user reports
    // of offensive forum posts.
define("EMAIL_FROM", "noreply@music-match.org");
define("EMAIL_FROM_NAME", "Music Match");

//-------------- Delete Account
//define("DELETE_DELAY", 2);
    // When deleting an account, invalidate the authenticator and then wait this
    // many seconds before proceeding with the delete.  This is intended to give the
    // an existing scheduler request sufficient time to complete.  Some projects
    // might want to increase this to a longer time.  Simply uncomment and set the
    // delay to what the project needs.
    
//-------------- Caching

//define("MEMCACHE_SERVERS", "127.0.0.1:11211");

//-------------- CSS styling

// add your own stylesheets (included after bootstrap)
define("STYLESHEET", "custom_dark.css");
//define("STYLESHEET2", "bbb.css");

//-------------- enable/disable web features

define("FORUM_QA_MERGED_MODE", true);
    // Set to true to merge Message boards and Q&A section
define ("DISABLE_PROFILES", true);
    // enable profiles only after enabling reCAPTCHA
    // https://boinc.berkeley.edu/trac/wiki/ProtectionFromSpam
define("USE_STOPFORUMSPAM", true);
    // use http://www.stopforumspam.com to suppress spammer accounts
define("RPC_DEBUG", false);
    // Enables PHP error messages in public Web RPCs
define("TERMSOFUSE_FILE", "../../terms_of_use.txt");
    // Defines the location of the terms of use file. Default location is in the project directory.

//-------------- Project-specific preferences

define("DARK_MODE", false);

//-------------- PHPMailer

// If you use PHPMailer, uncomment the following
// and complete the function definition based on your SMTP server
// (not all fields may be needed)
//
if (0) {
function make_php_mailer() {
    $mail = new PHPMailer();
    $mail->IsSMTP();
    //$mail->Mailer = 'smtp';
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = "tls";
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 587;
    $mail->Username = "john.doe@gmail.com";
    $mail->Password = "xxx";
        // Google's application-specific password,
        // if you are using the 2-Step Verification: 16 characters, no spaces.
        // OR: Put here the regular Gmail password if you are not using the
        // 2-Step Verification with your Gmail account.
        // See https://support.google.com/accounts/answer/185833?hl=en";
    $mail->SetFrom('admin@boincproject.com', 'John Doe');
    $mail->AddReplyTo("admin@boincproject.com", "John Doe");
    $mail->From = "admin@boincproject.com";
    return $mail;
}
}

//-------------- Header and footer

// Put your project title and logo here
// If you include any links, prepend URL with $prefix
// if you need to open divs here close them in project_footer()
//

$show_home_link = true;

function project_banner($title, $prefix, $is_main) {
    mm_navbar(secure_url_base(), get_logged_in_user(false), false);
    if ($title) {
        echo "<h2>$title</h2>\n";
    }
}

function project_footer($show_return, $show_date, $prefix) {
    global $show_home_link, $g_logged_in_user;
    if ($g_logged_in_user && $show_home_link) {
        echo "<p><p>";
        home_button();
    }
    return;
    // If you include any links, prepend URL with $prefix
    //
    echo '<br>
        <p class="text-center"> &copy;'.gmdate("Y ").COPYRIGHT_HOLDER.'</p>
    ';
    if ($show_date) {
        $t = time_str(time());
        echo "<center><small>".tra("Generated")." $t</small><center>\n";
    }
}

//-------------- Ops access control

// Authorize access to administrative pages.
// You can check for login, IP address, or whatever you want.
//
function auth_ops_example() {
    // if running from cmdline, skip checks
    //
    if (!$_SERVER['REMOTE_ADDR']) {
        return;
    }

    // example: require login as a particular user (id 1 in this case)
    //
    if (0) {
        auth_ops_userid(array(1));
        return;
    }

    // example: require that logged-in user have ADMIN or DEV flags
    // set in their forum_prefs.privilege
    //
    if (0) {
        auth_ops_privilege();
        return;
    }
}

//-------------- Customizable functions

function show_profile_heading1() {
    return tra("Your personal background.");
}

function show_profile_question1() {
    return tra("Tell us about yourself. You could tell us where you're from, your age, occupation, hobbies, or anything else about yourself.");
}

function show_profile_heading2() {
    return tra("Your opinions about %1", PROJECT);
}

function show_profile_question2() {
    return tra("Tell us your thoughts about %1<ol>
    <li>Why do you run %1?
    <li>What are your views about the project?
    <li>Any suggestions?
    </ol>", PROJECT);
}

function project_workunit($wu){
    // shown in the workunit page
}

function project_user_summary($user){
    // shown in the user summary page
}

function project_user_page_private($user){
    // shown in the private account page
}

function project_forum_post_rules() {
    // additional forum post rules
    return "";
}

function project_delete_account($user) {
    // a project defined function for deleting users if neither
    // obfuscate_account($user) or wipe_account($user) in 
    // inc/delete_account.inc meets the need of the project
    die("This function must be implemented before it can be used");
}

function mm_navbar(
    $url_prefix,
        // prefix for links; needed for pages not in top dir
    $user,
        // logged-in user, if any
    $fixed=false,
        // if true, navbar is fixed at top of page.
        // NOTE: if you do this, you must set a global var $fixed_navbar
        // to true at compile time
        // (it needs to be set when page_head() is called).
    $inverse=false
        // white on black?
) {
    global $master_url;

    $brand = "<a class=\"navbar-brand\" href=$master_url title='Music Match front page'>Music Match</a>";
    navbar_start($brand, $fixed, $inverse);

    $x = [];
    $x[] = [tra("About"), $url_prefix."intro.php"];
    $x[] = [tra("Contact"), $url_prefix."contact.php"];
    navbar_menu(tra("Project"), $x);

    navbar_menu(tra("Community"), [
        [tra("Message boards"), $url_prefix."forum_index.php"],
        ["Community preferences", "edit_forum_preferences_form.php"],
        ["User name lookup", "name_lookup.php"],
        ["Ensemble name lookup", "name_lookup.php?ensemble=1"]
    ]);
    if ($user) {
        navbar_menu(tra("Account"), [
            [tra("Settings"), $url_prefix."settings.php"]
        ]);
    }
    navbar_menu(tra("Search"), [
        ["Composers", "cp_search.php?role=0"],
        ["Performers", "cp_search.php?role=1"],
        ["Technicians", "tech_search.php"],
        ["Ensembles", "ensemble_search.php"],
        ["Teachers", "teacher_search.php"]
    ]);

    navbar_right($user);
    navbar_end();
}

?>
