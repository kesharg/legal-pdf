<style>
#cookie-banner {
  display: none; /* Initially hidden */
  position: fixed;
  bottom: 20px; /* Adjusts the distance from the bottom */
  left: 20px; /* Adjusts the distance from the left */
  width: 300px; /* Adjust the width of the popup */
  background-color: #2c2c2c;
  color: #fff;
  padding: 20px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
  border-radius: 10px;
  z-index: 1000;
}

#cookie-banner h3 {
  margin: 0 0 10px;
  font-size: 18px;
}

#cookie-banner p {
  font-size: 14px;
  line-height: 1.5;
}

#cookie-banner button {
  display: block;
  width: 100%;
  margin: 5px 0;
  padding: 10px;
  border: none;
  border-radius: 5px;
  font-size: 14px;
  cursor: pointer;
}

#cookie-banner button:first-child {
  background-color: #007bff;
  color: #fff;
}

#cookie-banner button:nth-child(2) {
  background-color: #6c757d;
  color: #fff;
}

#cookie-banner button:last-child {
  background-color: transparent;
  color: #4aa1ff;
  border: 1px solid #4aa1ff;
}

/* Overlay (fade background) */
.overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5); /* Semi-transparent black */
  z-index: 999; /* Below the modal */
  display: none; /* Hidden by default */
  opacity: 0; /* Start transparent */
  transition: opacity 0.3s ease; /* Fade-in effect */
}

/* Modal content */
.modal-content {
  text-align: center;
}

/* Show overlay and modal */
.overlay.active,
.modal.active {
  display: block;
  opacity: 1; /* Fully visible */
}

/* Mobile View */
@media only screen and (max-width: 600px) {
  #cookie-banner {
    width: 90%; /* Make the banner take up more space on smaller screens */
    left: 5%; /* Align the banner properly */
    bottom: 10px; /* Slightly adjust the position from the bottom */
    padding: 15px; /* Adjust padding for smaller screens */
  }

  #cookie-banner h3 {
    font-size: 16px; /* Adjust the font size for mobile */
  }

  #cookie-banner p {
    font-size: 12px; /* Adjust paragraph font size */
  }

  #cookie-banner button {
    font-size: 12px; /* Adjust button font size for mobile */
  }
}
</style>
<footer class="bottom-section mb-3">
    <div class="container">
        <div class="row">
            <div class="col">
                <hr />
            </div>
        </div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 footer-nav-area">
            <div class="col">
                <div class="row align-items-center flex-column">
                    <div class="col">
                        <a href="{{ url('/') }}" class="footer-img block">
                            <img src="{{ asset('./web/assets/img/resize-image/android-chrome-192x192.png') }}"
                                alt="site-logo">
                        </a>
                    </div>
                    <div class="col">
                        <ul class="dot-ul-ui">
                            <li><a href="https://www.facebook.com/CORRbuilder-113474187493980" target="_blank"><i
                                        class="fa-brands fa-square-facebook facebook-blue"></i></a></li>
                            <li><a href="https://www.instagram.com/LegalPDF/" target="_blank"><i
                                        class="fa-brands fa-square-instagram instagram-black"></i></a></li>
                            <li><a href="https://twitter.com/LegalPDF" target="_blank"><i
                                        class="fa-brands fa-square-twitter twitter-blue"></i></a></li>
                            <li><a href="https://www.linkedin.com/company/corrbuilder/" target="_blank"><i
                                        class="fa-brands fa-linkedin linkedin-blue"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col footer-responsive" id="contact">
                <h3 class="footer-title"><span>{{ localize('london') }}</span> OFFICE</h3>
                <ul class="footer-links">
                    <li><i class="fa-solid fa-phone"></i><a href="tel:+44 208 5053 311">+44 208 5053 311</a></li>
                    <li><i class="fa-solid fa-phone"></i><a href="tel:+44 770 3647 933">+44 770 3647 933</a></li>
                    <li><i class="fa-solid fa-location-dot"></i><a
                            href="https://www.google.com/maps/search/18+Elington+Road,+London+E8+3PA/@51.5414638,-0.0579563,18z/data=!3m1!4b1"
                            target="_blank">{{ localize('address_london') }}</a></li>
                </ul>
            </div>
            {{-- <div class="col footer-responsive">
                <h3 class="footer-title"><span>{{ localize('jerusalem') }}</span> OFFICE</h3>
                <ul class="footer-links">
                    <li><i class="fa-solid fa-phone"></i><a href="tel:+972 2641 11 55">+972 2641 11 55</a></li>
                    <li><i class="fa-solid fa-phone"></i><a href="tel:+972 5384 88 60">+972 5384 88 60</a></li>
                    <li><i class="fa-solid fa-location-dot"></i><a
                            href="https://www.google.com/maps/place/City+Home+Tel+Aviv+-+Yehoshua+Bin+Nun/@32.0878252,34.7804299,19.54z/data=!4m6!3m5!1s0x151d4bec5e29811f:0xde99f25ce5db744!8m2!3d32.087982!4d34.7803032!16s%2Fg%2F11h12b9zbv"
                            target="_blank">{{ localize('address_london') }}</a></li>
                </ul>
            </div> --}}
            <div class="col">
                <div class="img-holder">
                    <img src="{{ asset('./web/assets/img/worldmap.png') }}" alt="world-map">
                </div>
            </div>
        </div>
        <div class="row justify-content-end language-area">
            <div class="col-auto">
                <div class="language-dropdown-wrapper">
                    <select onchange="language(this.value, this.options[this.selectedIndex].getAttribute('lang-direction') );" class="form-select language-dropdown">
                        @foreach(getCountries() as $country)
                            <option value="{{ $country->code }}" lang-direction="{{ $country->direction }}"
                                {{ getSession('lang') === $country->code ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 copyright-area mt-3">
            <div class="col">
                <p class="copyright">{{ localize('copyright_notice') }} <span>{{ localize('company_name') }}</span></p>
            </div>
            <div class="col">
                <ul class="extra-links">
                    <li><a href="{{ route('web.page', ['slug' => 'our-privacy-policy']) }}">{{ localize('privacy_policy') }}</a></li>
                    <li><a href="{{ route('web.page', ['slug' => 'our-cookie-policy']) }}">{{ localize('cookie_policy') }}</a></li>
                    <li><a href="{{ route('web.page', ['slug' => 'terms-of-service']) }}">{{ localize('terms_of_service') }}</a></li>
                    <li><a href="{{ route('web.page', ['slug' => 'google-api-services']) }}">{{ localize('google_api_services') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
    <!-- Cookie Consent Popup -->
<div id="cookie-popup" style="display: none; position: fixed; bottom: 10%; left: 50%; transform: translateX(-50%); width: 90%; max-width: 500px; background: #fff; border: 1px solid #ddd; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); padding: 20px; z-index: 1000;">
  <h3 style="margin-top: 0;">We Use Cookies</h3>
  <p style="font-size: 14px; line-height: 1.5;">
    We use cookies to improve your experience. By clicking "Accept All," you consent to the use of all cookies. You can also customize your preferences.
    See our <a href="/cookie-policy" style="text-decoration: underline; color: blue;">Cookie Policy</a>.
  </p>
  <div style="text-align: center; margin-top: 20px;">
    <button onclick="acceptAllCookies()" style="background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">Accept All</button>
    <button onclick="acceptNecessaryCookies()" style="background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">Necessary Only</button>
    <button onclick="openCustomizeSettings()" style="background-color: #ffc107; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">Customize</button>
  </div>
</div>

<!-- Cookie Consent Popup -->
<div id="cookie-banner">
  <p>
  By clicking “Accept all cookies”, you agree Stack Exchange can store cookies on your device and disclose information in accordance with our Cookie Policy.
  <a href="{{ route('web.page', ['slug' => 'our-cookie-policy']) }}" style="color: #4aa1ff; text-decoration: underline;">Cookie Policy</a>
  </p>
  <button onclick="acceptAllCookies()">Accept All Cookies</button>
  <button onclick="acceptNecessaryCookies()">Necessary Cookies Only</button>
  <button onclick="openCookieSettings()">Customize Settings</button>
</div>


<!-- Cookie Settings Modal -->
<div id="cookie-settings-modal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 400px; background: #fff; color: #333; border: 1px solid #ddd; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3); padding: 20px; z-index: 1001;">
  <h3>Customize Cookie Settings</h3>
  <form id="cookie-settings-form">
    <label>
      <input type="checkbox" disabled checked> Essential Cookies (Required)
    </label><br>
    <label>
      <input type="checkbox" id="performance-cookies"> Performance Cookies
    </label><br>
    <label>
      <input type="checkbox" id="functional-cookies"> Functional Cookies
    </label><br>
    <br><br>
    <button type="button" onclick="savePreferences()" style="background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Save Preferences</button>
    <button type="button" onclick="closeCookieSettings()" style="background-color: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
  </form>
</div>
<div id="overlay" class="overlay"></div>

</footer>
@push('extra-scripts')
<script>
 // Show the popup only if no cookie preferences are saved
window.onload = function () {
  if (!getCookie('cookieConsent') && !getCookie('cookiePreferences')) {
    document.getElementById('cookie-banner').style.display = 'block';
  }
};

function acceptAllCookies() {
  setCookie('cookieConsent', 'all', 365); // Save preference for all cookies
  hideCookieBanner();
}

function acceptNecessaryCookies() {
  setCookie('cookieConsent', 'necessary', 365); // Save preference for necessary cookies only
  hideCookieBanner();
}

function openCookieSettings() {
    document.getElementById('overlay').classList.add('active');

  document.getElementById('cookie-settings-modal').style.display = 'block';
}

function closeCookieSettings() {
    document.getElementById('overlay').classList.remove('active');

  document.getElementById('cookie-settings-modal').style.display = 'none';
}

function savePreferences() {
  const performance = document.getElementById('performance-cookies').checked;
  const functional = document.getElementById('functional-cookies').checked;

  // Store user preferences as a JSON string
  const preferences = { performance, functional };
  setCookie('cookiePreferences', JSON.stringify(preferences), 365);

  closeCookieSettings();
  hideCookieBanner();
}

// Hide the cookie banner
function hideCookieBanner() {
  document.getElementById('cookie-banner').style.display = 'none';
}

// Utility: Set Cookie
function setCookie(name, value, days) {
  const date = new Date();
  date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000)); // Expiry time
  const expires = "expires=" + date.toUTCString();
  const path = "path=/;";
  const domain = "domain=" + (document.domain.match(/[^\.]*\.[^.]*$/)[0]) + ";";
  document.cookie = name + "=" + value + ";" + expires + path + domain ;
}

// Utility: Get Cookie
function getCookie(name) {
  const cookies = document.cookie.split(';');
  for (let i = 0; i < cookies.length; i++) {
    const cookie = cookies[i].trim();
    if (cookie.startsWith(name + '=')) {
      return cookie.substring(name.length + 1);
    }
  }
  return null;
}


</script>
<script src="{{ asset('web/assets/js/app.js?v='.time()) }}"></script>
@endpush
