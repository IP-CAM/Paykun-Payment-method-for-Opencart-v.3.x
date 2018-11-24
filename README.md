# Opencart-V3.X
Opencart Version 3.X

# <h3>How To Generate Access token and API Secret :</h3>
You can find your Merchant Id in Paykun Dashboard.

You can generate Or Regenerate Access token and API Secret from login into your paykun admin panel, Then Go To : Settings -> Security -> API Keys. There you will find the generate button if you have not generated api key before.

If you have generated api key before then you will see the date of the api key generate, since you will not be able to retrieve the old api key (For security reasons) we have provided the re-generate option, so you can re-generate api key in case you have lost the old one.

Note : Once you re-generate api key your old api key will stop working immediately. So be cautious while using this option.

# <h3>Prerequisite</h3>
    Merchant Id (Please read 'How To Generate Access token and API Secret :')
    Access Token (Please read 'How To Generate Access token and API Secret :')
    Encryption Key (Please read 'How To Generate Access token and API Secret :')
    Wordpress 4.x compatible Woo-Commerce version must be installed and other payment method working properly.

# <h3>Installation</h3>
Note: Please backup your running source code and database first.
  1. Download the zip and extract it to the some temporary location.
  2. Now copy the directory admin, catalog, system folder from the extracted zip and replace this directories
     on the location yourwebsite-opencart-installed-path/override admin directory and other too.   
  3. Now login to the admin panel and do same as given in screen shot.
        
        ![alt text](https://github.com/paykun-code/Opencart-V3.X/blob/master/screenshots/Dashboard1.png)
        
        Select Payment extension from the dropdown
        ![alt text](https://github.com/paykun-code/Opencart-V3.X/blob/master/screenshots/Extensions-Payment-selection.png)
        
        Click first on Install and then Edit button  
        ![alt text](https://github.com/paykun-code/Opencart-V3.X/blob/master/screenshots/Extensions-installation-enter-credentials.png)
        
        Enter all the required fields.
        ![alt text](https://github.com/paykun-code/Opencart-V3.X/blob/master/screenshots/Extensions-Credentials-enter.png)
  
  4. Then click on save button to save all the settings.               
Now you will be able to see Paykun Payment method in the checkout page.

#<h3> In case of any query, please contact to support@paykun.com.</h3>  