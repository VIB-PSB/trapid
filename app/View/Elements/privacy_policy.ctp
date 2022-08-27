<?php
/**
 * TRAPID privacy policy collapisble panel
 */
?>

<div class="panel panel-slim panel-default" id="privacy-policy-panel" role="tablist">
    <div class="panel-heading">TRAPID privacy policy</div>
    <div class="list-group">

        <a class="list-group-item" data-toggle="collapse" data-target="#what_data" data-parent="#privacy-policy-panel" role="button">
            <i class="material-icons md-18 text-muted">contact_support</i>
            What personal data does TRAPID collect?
        </a>
        <div class='collapse' id='what_data'>
            <div class="list-group-item faq-answer">
                <p class='text-justify'>
                    When you register to use TRAPID, the email and user data you provide (organization and country) are collected.
                    <br/>
                    By browsing TRAPID, anonymous user statistics are gathered using Google Analytics.
                </p>
            </div>
        </div>

        <a class="list-group-item" data-toggle="collapse" data-target="#how_data_collected" data-parent="#privacy-policy-panel" role="button">
            <i class="material-icons md-18 text-muted">contact_support</i>
            How is the personal data collected?
        </a>
        <div class='collapse' id='how_data_collected'>
            <div class="list-group-item faq-answer">
                <p class='text-justify'>
                    You directly provide your personal data:
                <ul>
                    <li>when you register for TRAPID</li>
                    <li>when you use TRAPID, via your browser's cookies</li>
                </ul>
                </p>
            </div>
        </div>

        <a class="list-group-item" data-toggle="collapse" data-target="#how_data_used" data-parent="#privacy-policy-panel" role="button">
            <i class="material-icons md-18 text-muted">contact_support</i>
            How is the personal data used?
        </a>
        <div class='collapse' id='how_data_used'>
            <div class="list-group-item faq-answer">
                <p class='text-justify'>
                    Your personal data is only used for identification and authentication purposes,
                    in order to safeguard your TRAPDI experiment data from unauthorized access.
                </p>
            </div>
        </div>

        <a class="list-group-item" data-toggle="collapse" data-target="#how_data_stored" data-parent="#privacy-policy-panel" role="button">
            <i class="material-icons md-18 text-muted">contact_support</i>
            How is the personal data stored?
        </a>
        <div class='collapse' id='how_data_stored'>
            <div class="list-group-item faq-answer">
                <p class='text-justify'>
                    Your personal data is stored at servers of Ghent University and VIB.
                    All common security protocols are followed, and only the minimum amount of information is stored.
                </p>
            </div>
        </div>

        <a class="list-group-item" data-toggle="collapse" data-target="#marketing" data-parent="#privacy-policy-panel" role="button">
            <i class="material-icons md-18 text-muted">contact_support</i>
            Is the personal data used for marketing purposes?
        </a>
        <div class='collapse' id='marketing'>
            <div class="list-group-item faq-answer">
                <p class='text-justify'>
                    We will not use your personal information for marketing purposes,
                    nor will your personal data be shared with outside partners for marketing purposes.
                </p>
            </div>
        </div>

        <a class="list-group-item" data-toggle="collapse" data-target="#rights" data-parent="#privacy-policy-panel" role="button">
            <i class="material-icons md-18 text-muted">contact_support</i>
            What are your personal data protection rights?
        </a>
        <div class='collapse' id='rights'>
            <div class="list-group-item faq-answer">
                <p class='text-justify'>
                    You are entitled to the following rights:
                <ul>
                    <li>
                        <span class='rights-title'>The right to access:</span>
                        <span class='rights-explanation'>You have the right to request whether you are present in the TRAPID system, and receive information about the personal data that have been processed.<span>
                    </li>
                    <li>
                        <span class='rights-title'>The right to rectification:</span>
                        <span class='rights-explanation'>You have the right to request that any incorrect information is corrected.</span>
                    </li>
                    <li>
                        <span class='rights'>The right to erasure:</span>
                        <span class='rights-explanation'>You have the right to request that we delete your login information from the TRAPID system.</span>
                    </li>
                </ul>
                </p>
                <p class='text-justify'>
                    If you make a request, we have one month to respond to you.<br/>
                    If you would like to exercise any of these rights, please <?=$this->Html->link("contact us by email",array("controller"=>"documentation","action"=>"contact"));?>.
                </p>
                <p class='text-justify'>
                    You also have the right to file a complaint with the Belgian Data Protection Authority in case you would be of the opinion that we fail to respect your personal data protection rights.
                    You can find their contact information on this <a target='_blank' href='https://www.gegevensbeschermingsautoriteit.be/' class="linkout">website</a>.
                </p>
            </div>
        </div>

        <a class="list-group-item" data-toggle="collapse" data-target="#what_cookies" data-parent="#privacy-policy-panel" role="button">
            <i class="material-icons md-18 text-muted">contact_support</i>
            What are cookies?
        </a>
        <div class='collapse' id='what_cookies'>
            <div class="list-group-item faq-answer">
                <p class='text-justify'>
                    Cookies are text files placed on your computer to collect standard Internet log information and visitor behavior information.
                    <br/>
                    When you visit our websites, we may collect information from you automatically through cookies or similar technology.
                    <br/><br/>
                    For further information, visit <a href='http://allaboutcookies.org' target='_blank' class='linkout'>allaboutcookies.org</a>.
                </p>
            </div>
        </div>

        <a class="list-group-item" data-toggle="collapse" data-target="#how_cookies" data-parent="#privacy-policy-panel" role="button">
            <i class="material-icons md-18 text-muted">contact_support</i>
            How are cookies used in TRAPID?
        </a>
        <div class='collapse' id='how_cookies'>
            <div class="list-group-item faq-answer">
                <p class='text-justify'>
                    TRAPID uses cookies in a range of ways to improve your experience on our website, including:
                <ul>
                    <li>Keeping you signed in for authentication purposes</li>
                    <li>Understanding how you use our website</li>
                </ul>
                </p>
            </div>
        </div>

        <a class="list-group-item" data-toggle="collapse" data-target="#how_contact_us" data-parent="#privacy-policy-panel" role="button">
            <i class="material-icons md-18 text-muted">contact_support</i>
            How can you contact us?
        </a>
        <div class='collapse' id='how_contact_us'>
            <div class="list-group-item faq-answer">
                <p class='text-justify'>
                    Please visit the <?=$this->Html->link("contact page",array("controller"=>"documentation","action"=>"contact"));?> to find our contact details.
                </p>
            </div>
        </div>
    </div>
</div>
