
This Street View Plugin creats a 'Streets' custom post type which gets the data from "https://geographic.org/streetview/netherlands/north_holland/amsterdam.html" and if the streetname starts from "Korte" or end with "gracht" then it will not be added to the list. 
It sorts based on the second (or third or fourth in case second letter was empty or '.' ) letter of the street name.

Due to the use of the transient, the data can be refreshed maximum only once per minute. 
Every post contains a street name and a related thumbnail - these are then saved to the database.

To activate the plugin you can run this WP command in the plugins folder:


`$ wp plugin activate streetview`

You can tell the plugin is active as it will change the wp admin background to a lovely shade of purple :)

To deactivate the plugin you can run this WP command when in the plugins folder:


`$ wp plugin deactivate streetview`

The street post type is filled by the data as soon as the class is initiated (the plugin is activated).
Because of the transient, this makes sure that the data will be refreshed not more than once in a minute.  


### How to use? ###
By including this code you can create a list of streets on the page and if you click the street name you go to the street page with a related thumbnail.
  
``` 
   <?php
        $args = array(
                'post_type' => 'streets',
                'order'          => 'ASC',
            );
        $query = new WP_Query( $args );

        if( $query->have_posts() ) :
            while ( $query->have_posts() ) : $query->the_post(); 
                $posts = $query->posts; ?>
                <ul>
                    <?php foreach($posts as $post): ?>
                    <li><a href="<?php echo $post->guid; ?>"><?php echo $post->post_title;?></a></li>
                    <?php endforeach; ?>
                </ul>  
            <?php
            endwhile;
        endif;
        wp_reset_query();
    ?> 
```


### Important information ###
- Make sure to set the control variable for the number of results that you wish to save to the datbase, this has been added to control the amount of saved data. This is set in streetview.php within the $numberOfResults variable.


