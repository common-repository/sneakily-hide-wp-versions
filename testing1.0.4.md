# Testing - 1.0.4

- Activate (cli)                                                    
- Activate
    - Create mu-plugin - all perms
Logged in - admin:
    - Meta name (multiple pages)                                    
    - RSS/Atom/etc                                                  
    - Admin - emoji, load-styles, thickbox, load-scripts etc        
    - Post page - emoji, dashicons                                  
    - load-scripts, load-styles .php (ETag)                         
    - wp-admin install.php blocked
    - wp-admin upgrade.php blocked
    - Force increase                                                
    - Admin - mobile web                                            
    - Admin - Wordpress app                     
    - No WP DEBUG
    - Upgrade WP (version+, message, working wp+plugin)
    - Automatic upgrade WP
    - Upgrade plugin
    - Auto-upgrade plugin
Logged in - author:
    - Meta name (multiple pages)                                    
    - RSS/Atom/etc                                                  
    - Admin - emoji, load-styles, thickbox, load-scripts etc        
    - Post page - emoji, dashicons                                  
    - wp-admin install.php blocked
    - wp-admin upgrade.php blocked
Logged out:
    - Meta name (multiple pages)                                    
    - RSS/Atom/etc                                                  
    - Emoji, block library, wp-embed                                
    - Post page - emoji, block library                              
    - wp-admin install.php blocked
    - wp-admin upgrade.php blocked
    - WPScan except sums                                            
    - HTTP headers
    - No WP DEBUG
- Deactivate (manual, cli)                                          



#### 5.0
- Activate (cli)                                                    Y
- Activate (but empty db)                                           
Logged in - admin:
    - Meta name (multiple pages)                                    Y
    - RSS/Atom/etc                                                  Y      
    - Admin - emoji, load-styles, thickbox, load-scripts etc        Y
    - Post page - emoji, dashicons                                  Y
    - load-scripts, load-styles .php (ETag)                         Y
    - wp-admin install.php blocked                                  Y
    - wp-admin upgrade.php blocked                                  Y
    - Force increase                                                Y
    - Admin - mobile web                                            Y
    - Admin - Wordpress app                                         (N/A, i think)
    - No WP DEBUG                                                   Y
    - Upgrade WP (version+, message, working wp+plugin)             
    - Automatic upgrade WP
    - Manual upgrade WP                                             (N/A)

    - Upgrade plugin
    - Auto-upgrade plugin

Logged in - author:
    - Meta name (multiple pages)                                    
    - RSS/Atom/etc                                                  
    - Admin - emoji, load-styles, thickbox, load-scripts etc        
    - Post page - emoji, dashicons                                  
    - wp-admin install.php blocked                                  
Logged out:
    - Meta name (multiple pages)                                    
    - RSS/Atom/etc                                                  
    - Emoji, block library, wp-embed                                
    - Post page - emoji, block library                              
    - wp-admin install.php blocked                                  
    - WPScan except sums                                            
    - HTTP headers                                                  
    - No WP DEBUG                                                   
- Deactivate (manual, cli)                                          

#### Nginx5.0
- Activate (cli, empty db)                                          
- Activate (manual)                                                 
Logged in - admin:
    - Meta name (multiple pages)                                    Y
    - RSS/Atom/etc                                                  Y
    - Admin - emoji, load-styles, thickbox, load-scripts etc        Y
    - Post page - emoji, dashicons                                  Y
    - load-scripts, load-styles .php (ETag)                         Y
    - wp-admin install.php blocked                                  Y
    - wp-admin upgrade.php blocked                                  Y
    - Force increase                                                Y
    - Admin - mobile web                                            Y
    - Admin - Wordpress app                                         (N/A, i think)
    - No WP DEBUG                                                   Y
    - Upgrade WP (version+, message, working wp+plugin)             
    - Manual upgrade WP                                             

    - Upgrade plugin
    - Auto-upgrade plugin

Logged in - author:
    - Meta name (multiple pages)                                    
    - RSS/Atom/etc                                                  
    - Admin - emoji, load-styles, thickbox, load-scripts etc        
    - Post page - emoji, dashicons                                  
    - wp-admin install.php blocked                                  
Logged out:
    - Meta name (multiple pages)                                    Y
    - RSS/Atom/etc                                                  Y
    - Emoji, block library, wp-embed                                Y
    - Post page - emoji, block library                              Y
    - wp-admin install.php blocked                                  Y
    - WPScan except sums                                            Y
    - HTTP headers                                                  Y
    - No WP DEBUG                                                   Y
- Deactivate (manual, cli)                                          

#### Containerless Apache 5.0

- Activate (cli)                                                    
    - Create mu plugin                                              Y
- Activate (but empty db)                                           
Logged in - admin:
    - Meta name (multiple pages)                                    
    - RSS/Atom/etc                                                  
    - Admin - emoji, load-styles, thickbox, load-scripts etc        
    - Post page - emoji, dashicons                                  
    - load-scripts, load-styles .php (ETag)                         
    - wp-admin install / upgrade.php                                
    - Force increase                                                
    - Admin - mobile web                                            N/A
    - Admin - Wordpress app                                         N/A
(   - abspath.php direct writable )                                 N/A
(   - abspath.php FTPable )                                         
(   - change perms -> abspath.php created, message goes )           N/A
    - No WP DEBUG                                                   
    - Upgrade WP (version+, message, working wp+plugin)             

    - Upgrade plugin
    - Auto-upgrade plugin

Logged in - author:
    - Meta name (multiple pages)                                    
    - RSS/Atom/etc                                                  
    - Admin - emoji, load-styles, thickbox, load-scripts etc        
    - Post page - emoji, dashicons                                  
    - wp-admin install / upgrade.php                                
Logged out:
    - Meta name (multiple pages)                                    
    - RSS/Atom/etc                                                  
    - Emoji, block library, wp-embed                                
    - Post page - emoji, block library                              
    - wp-admin install / upgrade.php                                
    - WPScan except sums                                            
    - HTTP headers                                                  
    - No WP DEBUG                                                   
- Deactivate (manual, cli)                                          

#### 5.8.1

Basic functionality:
- Activate (cli)                                                    
- Activate (but empty db)                                           
Logged in - admin:
    - Meta name (multiple pages)                                    Y
    - RSS/Atom/etc                                                  Y
    - Admin - emoji, load-styles, thickbox, load-scripts etc        Y
    - Post page - emoji, dashicons                                  Y
    - load-scripts, load-styles .php (ETag)                         Y
    - wp-admin install / upgrade.php                                
    - Force increase                                                Y
    - Admin - mobile web                                            
    - Admin - Wordpress app                                         
(   - abspath.php direct writable )                                 N/A
(   - abspath.php FTPable )                                         N/A
(   - change perms -> abspath.php created, message goes )           N/A
    - No WP DEBUG                                                   
    - Upgrade WP (version+, message, working wp+plugin)             
    - Manual upgrade WP

    - Upgrade plugin
    - Auto-upgrade plugin

Logged in - author:
    - Meta name (multiple pages)                                    
    - RSS/Atom/etc                                                  
    - Admin - emoji, load-styles, thickbox, load-scripts etc        
    - Post page - emoji, dashicons                                  
    - wp-admin install / upgrade.php                                
Logged out:
    - Meta name (multiple pages)                                    Y
    - RSS/Atom/etc                                                  Y
    - Emoji, block library, wp-embed                                Y
    - Post page - emoji, block library                              Y
    - wp-admin install / upgrade.php                                
    - WPScan except sums                                            
    - HTTP headers                                                  
    - No WP DEBUG                                                   
- Deactivate (manual, cli)                                          


#### Nginx5.8.1

Basic functionality:
- Activate (cli)                                                    
- Activate (manual)                                                 
Logged in - admin:
    - Meta name (multiple pages)                                    Y
    - RSS/Atom/etc                                                  Y
    - Admin - emoji, load-styles, thickbox, load-scripts etc        Y
    - Post page - emoji, dashicons                                  Y
    - load-scripts, load-styles .php (ETag)                         Y
    - wp-admin install / upgrade.php                                
    - Force increase                                                Y
    - Admin - mobile web                                            
    - Admin - Wordpress app                                         
(   - abspath.php direct writable )                                 N/A
(   - abspath.php FTPable )                                         N/A
(   - change perms -> abspath.php created, message goes )           N/A
    - No WP DEBUG                                                   
    - Upgrade WP (version+, message, working wp+plugin)             N/A
    - Manual upgrade WP

    - Upgrade plugin
    - Auto-upgrade plugin

Logged in - author:
    - Meta name (multiple pages)                                    
    - RSS/Atom/etc                                                  
    - Admin - emoji, load-styles, thickbox, load-scripts etc        
    - Post page - emoji, dashicons                                  
    - wp-admin install / upgrade.php                                
Logged out:
    - Meta name (multiple pages)                                    Y
    - RSS/Atom/etc                                                  Y
    - Emoji, block library, wp-embed                                Y
    - Post page - emoji, block library                              
    - wp-admin install / upgrade.php                                
    - WPScan except sums                                            
    - HTTP headers                                                  
    - No WP DEBUG                                                   
- Deactivate (manual, cli)                                          


## Permissions tests (WP version independent, 5.8 version only, Apache)



## Permissions tests (WP version independent, 5.8 version only, Nginx)

N/A


## Multisite / Network

(N/A - TODO later version)




## Pro – complete Wpscan fail

(free version)


## Pro – Theme versions – core, others

(free version)

## Pro – actions and filters

(free version)

