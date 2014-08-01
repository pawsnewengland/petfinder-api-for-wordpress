<?php

/* ======================================================================

	Plugin Name: Petfinder API for WordPress
	Plugin URI: https://github.com/cferdinandi/petfinder-api-for-wordpress
	Description: A collection of functions to help you display Petfinder listings on your WordPress site
	Version: 5.1.0
	Author: Chris Ferdinandi
	Author URI: http://gomakethings.com
	License: MIT

	Thanks Bridget Wessel's Petfinder Listings Plugin for getting me started.
	http://wordpress.org/extend/plugins/petfinder-listings/

	Several regex patterns were adapted from Chris Coyier.
	http://css-tricks.com/snippets/php/find-urls-in-text-make-links/

	Description formatting bug fix contributed by Nick Alonge.
	http://www.nsofttech.com/

 * ====================================================================== */

/* =============================================================
	YOUR SHELTER INFO
	Get your shelter info from Petfinder.
 * ============================================================= */

function get_petfinder_data($api_key, $shelter_id, $count, $pet = '', $offset = 0) {

	// If no specific pet is specified
	if ( $pet == '' ) {
		// Create request URL for all pets from the shelter
		$request_url = 'http://api.petfinder.com/shelter.getPets?key=' . $api_key . '&count=' . $count . '&id=' . $shelter_id . '&offset=' . $offset . '&status=A&output=full';
	}

	// If a specific pet IS specified
	else {
		// Create a request URL for that specific pet's data
		$request_url = 'http://api.petfinder.com/pet.get?key=' . $api_key . '&id=' . $pet;
	}

	// Request data from Petfinder
	$petfinder_data = @simplexml_load_file( $request_url );

	// If data not available, don't display errors on page
	if ($petfinder_data === false) {}

	return $petfinder_data;

}





/* =============================================================
	UPDATE PAGE TITLE
	Updates <title> element in the markup.
 * ============================================================= */

function update_pretty_title( $title ) {
	if ( isset( $_GET['pet-name'] ) && $_GET['pet-name'] != '' ) {
		$pet = $_GET['pet-name'];
		$blog = get_bloginfo( 'name' );
		$title = $pet . ' | ' . $blog;
		return $title;
	} else {
		return $title;
	}
}
add_filter( 'wp_title', 'update_pretty_title', 10, 2 );





/* =============================================================
	CONVERSIONS
	Functions to convert default Petfinder return values into
	human-readable and/or custom descriptions.
 * ============================================================= */

// Convert Pet Size
function get_pet_size($pet_size) {
	if ($pet_size == 'S') return 'Small';
	if ($pet_size == 'M') return 'Medium';
	if ($pet_size == 'L') return 'Large';
	if ($pet_size == 'XL') return 'Extra Large';
	return 'Not Known';
}

// Convert Pet Age
function get_pet_age($pet_age) {
	if ($pet_age == 'Baby') return 'Puppy';
	if ($pet_age == 'Young') return 'Young';
	if ($pet_age == 'Adult') return 'Adult';
	if ($pet_age == 'Senior') return 'Senior';
	return 'Not Known';
}

// Convert Pet Gender
function get_pet_gender($pet_gender) {
	if ($pet_gender == 'M') return 'Male';
	if ($pet_gender == 'F') return 'Female';
	return 'Not Known';
}

// Convert Special Needs & Options
function get_pet_option($pet_option) {
	if ($pet_option == 'specialNeeds') return 'Special Needs';
	if ($pet_option == 'noDogs') return 'No Dogs';
	if ($pet_option == 'noCats') return 'No Cats';
	if ($pet_option == 'noKids') return 'No Kids';
	if ($pet_option == 'noClaws') return '';
	if ($pet_option == 'hasShots') return '';
	if ($pet_option == 'housebroken') return '';
	if ($pet_option == 'altered') return '';
	return 'Not Known';
}

// Convert plain text links to working links
function get_text_links($text) {

	// Regex pattern
	$url_filter = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';

	// If any URLs exist, convert them to links
	if ( preg_match( $url_filter, $text, $url ) ) {
	   return preg_replace( $url_filter, '<a href="' . $url[0] . '" rel="nofollow">' . $url[0] . '</a>', $text );
	} else {
	   return $text;
	}
}

// Convert plain text email addresses to working links
function get_text_emails($text) {

	// Regex pattern
	$email_filter = '/([a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4})/';

	// If any emails exist, convert them to links
	if ( preg_match( $email_filter, $text, $email ) ) {
		return preg_replace( $email_filter, '<a href="mailto:' . $email[0] . '">' . $email[0] . '</a>', $text );
	} else {
		return $text;
	}
}





/* =============================================================
	PET PHOTO SETTINGS
	Set size and number of pet photos.
 * ============================================================= */

function get_pet_photos($pet, $photo_size = 'medium', $limit = true, $before = '', $after = '') {

	// Set size
	if ( $photo_size == 'large' ) {
		$pet_photo_size = 'x';
	}
	if ( $photo_size == 'medium' ) {
		$pet_photo_size = 'pn';
	}
	if ( $photo_size == 'thumb_small' ) {
		$pet_photo_size = 't';
	}
	if ( $photo_size == 'thumb_medium' ) {
		$pet_photo_size = 'pnt';
	}
	if ( $photo_size == 'thumb_large' ) {
		$pet_photo_size = 'fpm';
	}

	// Define Variables
	$pet_photos = '';

	// If pet has photos
	if( count($pet->media->photos) > 0 ) {

		// For each photo, get photos that match the set size
		foreach ( $pet->media->photos->photo as $photo ) {
			foreach( $photo->attributes() as $key => $value ) {
				if ( $key == 'size' ) {
					if ( $value == $pet_photo_size ) {

						// If limit set on number of photos, get the first photo
						if ( $limit == true ) {
							$pet_photos = $before . '<img class="space-bottom-small" alt="Photo of ' . $pet_name . '" src="' . $photo . '">' . $after;
							break 2;
						}

						// Otherwise, get all of them
						else {
							$pet_photos .= $before . '<img class="space-bottom-small pf-img" alt="Photo of ' . $pet_name . '" src="' . $photo . '">' . $after;
						}

					}
				}
			}
		}
	}

	// If no photos have been uploaded for the pet
	else {
		$pet_photos = $before . '<img class="space-bottom-small" alt="No photo has been posted yet for ' . $pet_name . '" src="' . get_template_directory_uri() . '/img/nophoto.jpg">' . $after;
	}

	return $pet_photos;

}





/* =============================================================
	PET NAME CLEANUP
	Adjust formatting and remove special characters from pet names.
 * ============================================================= */

function get_pet_name($pet_name) {

	// Clean-up pet name
	$pet_name = array_shift(explode('-', $pet_name)); // Remove '-' from animal names
	$pet_name = array_shift(explode('(', $pet_name)); // Remove '(...)' from animal names
	$pet_name = array_shift(explode('[', $pet_name)); // Remove '[...]' from animal names
	$pet_name = array_shift(explode('"', $pet_name)); // Remove '"' from animal names
	$pet_name = strtolower($pet_name); // Transform names to lowercase
	$pet_name = ucwords($pet_name); // Capitalize the first letter of each name
	$pet_name = array_shift(explode('Local', $pet_name)); // Remove 'Local' from animal names
	$pet_name = trim($pet_name); // Remove trailing whitespace
	// $pet_name_scrub = array('Local' => ''); // Define strings to remove
	// $pet_name = strtr($pet_name, $pet_name_scrub); // Remove strings

	// Return pet name
	return $pet_name;

}





/* =============================================================
	PET DESCRIPTION CLEANUP
	Remove inline styling and empty tags from pet descriptions.
 * ============================================================= */

function get_pet_description($pet_description) {

	// Remove unwanted styling from pet description
	$pet_description = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $pet_description);// Remove inline styling
	$pet_description = preg_replace('/<font[^>]+>/', '', $pet_description); // Remove font tag
	$pet_description_scrub = array('<p></p>' => '', '<p> </p>' => '', '<p>&nbsp;</p>' => '', '<span></span>' => '', '<span> </span>' => '', '<span>&nbsp;</span>' => '', '<span>' => '', '</span>' => '', '<font>' => '', '</font>' => ''); // Define empty tags to remove
	$pet_description = strtr($pet_description, $pet_description_scrub); // Remove empty tags
	$pet_description = get_text_links($pet_description); // Convert plain text URLs to links
	$pet_description = get_text_emails($pet_description); // Convert plain text emails to links
	$pet_description = '<pre class="pf-description">' . $pet_description . '</pre>'; // Wrap in <pre> tags to preserve formatting

	// Return pet description
	return $pet_description;

}





/* =============================================================
	PET LIST CONDENSER
	Removes spacing and special characters from strings.
 * ============================================================= */

function pet_value_condensed($pet_value) {

	// Define characters to remove and remove them
	$condense_list = array('(' => '', ')' => '', '&' => '-', '/' => '-', '  ' => '-', ' ' => '-');
	$pet_value = strtr($pet_value, $condense_list);

	// Return condensed list
	return $pet_value;

}





/* =============================================================
	BREED LIST
	List of available breeds.
 * ============================================================= */

function get_breed_list($petfinder_data) {

	// Define Variables
	$breeds = '';
	$breed_list = '';
	$breed_list_1 = '';
	$breed_list_2 = '';

	// Get a list of breeds for each pet
	foreach ( $petfinder_data as $key => $data ) {
		$pets = $data->pets->pet;
		if ( $pets !== undefined && $pets !== null ) {
			foreach( $pets as $pet ) {
				foreach( $pet->breeds->breed as $pet_breed ) {
					$breeds .= $pet_breed . "|";
				}
			}
		}
	}

	// Remove duplicates, convert into an array and alphabetize
	$breeds = array_filter(array_unique(explode('|', $breeds)));
	asort($breeds);

	// Create breed checkbox
	function get_breed_checkbox($breed) {

		// Create a condensed version without spaces or special characters
		$breed_condensed = pet_value_condensed($breed);

		// Create checkbox
		$breed_checkbox .=  '<label>
								<input type="checkbox" class="pf-breeds" data-target=".' . $breed_condensed . '" checked>' .
								$breed .
							'</label>';

		// Return checkbox
		return $breed_checkbox;
	}

	// Split list of breeds in half
	$breed_count = count($breeds);
	$breeds_1 = array_slice($breeds, 0, $breed_count / 2);
	$breeds_2 = array_slice($breeds, $breed_count / 2);

	// Get checkboxes for first half of list
	foreach( $breeds_1 as $breed ) {
		$breed_list_1 .= get_breed_checkbox($breed);
	}

	// Get checkboxes for second half of list
	foreach( $breeds_2 as $breed ) {
		$breed_list_2 .= get_breed_checkbox($breed);
	}


	$breed_list =    '<div class="grid-6">
						<h3>Breeds</h3>
						<label>
							<input type="checkbox" class="pf-toggle-all" data-target=".pf-breeds" checked>
							Select/Unselect All
						</label>
					</div>
					<div class="grid-3">
						<form class="no-space-bottom">' .
							$breed_list_1 .
						'</form>
					</div>
					<div class="grid-3">
						<form>' .
							$breed_list_2 .
						'</form>
					</div>';

	// Return the list
	return $breed_list;

}





/* =============================================================
	SIZE LIST
	List of available size of pets.
 * ============================================================= */

function get_size_list($petfinder_data) {

	// Define Variables
	$sizes = '';
	$size_list = '';

	// Create a list of pet sizes
	foreach ( $petfinder_data as $key => $data ) {
		$pets = $data->pets->pet;
		if ( $pets !== undefined && $pets !== null ) {
			foreach( $pets as $pet ) {
				$sizes .= get_pet_size($pet->size) . "|";
			}
		}
	}

	// Remove duplicates, convert into an array, alphabetize and reverse list order
	$sizes = array_filter(array_unique(explode('|', $sizes)));
	asort($sizes);
	$sizes = array_reverse($sizes);

	// For each size of pet
	foreach( $sizes as $size ) {

		// Create a condensed version without spaces or special characters
		$size_condensed = pet_value_condensed($size);

		// Create a list
		$size_list .=   '<label>
							<input type="checkbox" class="pf-sort" data-target=".' . $size_condensed . '" checked>' .
								$size .
						'</label>';
	}

	$size_list =    '<div class="grid-third space-bottom-small">
						<h3>Size</h3>
						<form>' .
							$size_list .
						'</form>
					</div>';

	// Return the list
	return $size_list;

}





/* =============================================================
	AGE LIST
	List of available pet ages.
 * ============================================================= */

function get_age_list($petfinder_data) {

	// Define Variables
	$ages = '';
	$age_list = '';

	foreach ( $petfinder_data as $key => $data ) {
		$pets = $data->pets->pet;
		if ( $pets !== undefined && $pets !== null ) {
			foreach( $pets as $pet ) {
				$ages .= get_pet_age($pet->age) . "|";
			}
		}
	}

	// Remove duplicates, convert into an array and reverse list order
	$ages = array_reverse(array_filter(array_unique(explode('|', $ages))));

	// For each pet age
	foreach( $ages as $age ) {

		// Create a condensed version without spaces or special characters
		$age_condensed = pet_value_condensed($age);

		// Create a list
		$age_list .=    '<label>
							<input type="checkbox" class="pf-sort" data-target=".' . $age_condensed . '" checked>' .
								$age .
						'</label>';
	}

	$age_list =     '<div class="grid-third space-bottom-small">
						<h3>Age</h3>
						<form>' .
							$age_list .
						'</form>
					</div>';

	// Return the list
	return $age_list;

}





/* =============================================================
	GENDER LIST
	List of available pet genders.
 * ============================================================= */

function get_gender_list($petfinder_data) {

	// Define Variables
	$genders = '';
	$gender_list = '';

	// Create a list available pet genders
	foreach ( $petfinder_data as $key => $data ) {
		$pets = $data->pets->pet;
		if ( $pets !== undefined && $pets !== null ) {
			foreach( $pets as $pet ) {
				$genders .= get_pet_gender($pet->sex) . "|";
			}
		}
	}

	// Remove duplicates and convert into an array
	$genders = array_filter(array_unique(explode('|', $genders)));

	// For each pet gender
	foreach( $genders as $gender ) {

		// Create a condensed version without spaces or special characters
		$gender_condensed = pet_value_condensed($gender);

		// Create a list
		$gender_list .= '<label>
							<input type="checkbox" class="pf-sort" data-target=".' . $gender_condensed . '" checked>' .
								$gender .
						'</label>';
	}

	$gender_list =  '<div class="grid-third space-bottom-small">
						<h3>Gender</h3>
						<form>' .
							$gender_list .
						'</form>
					</div>';

	// Return the list
	return $gender_list;

}





/* =============================================================
	LOCATION LIST
	List of dog locations.
 * ============================================================= */

function get_pet_location($petfinder_data) {

	// Variables
	$out_of_state = false;
	$local = false;

	// Get available pet locations
	foreach ( $petfinder_data as $key => $data ) {
		$pets = $data->pets->pet;
		if ( $pets !== undefined && $pets !== null ) {
			foreach( $pets as $pet ) {
				if ( stripos( $pet->name, 'local' ) === false ) {
					$out_of_state = true;
				} else {
					$local = true;
				}
			}
		}
	}

	// Create out-of-state field
	if ( $out_of_state ) {
		$out_of_state_field =   '<label>
									<input type="checkbox" class="pf-sort" data-target=".out-of-state" checked>
									Out-of-State
								</label>';
	} else {
		$out_of_state_field = '';
	}

	// Create local field
	if ( $local ) {
		$local_field =  '<label>
							<input type="checkbox" class="pf-sort" data-target=".local" checked>
							Local
						</label>';
	} else {
		$local_field = '';
	}

	// Create form
	$locations_list =   '<div class="grid-half space-bottom-small">
							<h3>Location</h3>
							<form>' .
								$out_of_state_field .
								$local_field .
							'</form>
						</div>';

	return $locations_list;

}





/* =============================================================
	OPTIONS & SPECIAL NEEDS LIST
	List of all available special needs and options for pets.
 * ============================================================= */

function get_options_list($petfinder_data) {

	// Define Variables
	$options = '';
	$options_list = '';

	// Create a list of pet options and special needs
	foreach ( $petfinder_data as $key => $data ) {
		$pets = $data->pets->pet;
		if ( $pets !== undefined && $pets !== null ) {
			foreach( $pets as $pet ) {
				foreach( $pet->options->option as $pet_option ) {
					$options .= get_pet_option($pet_option) . "|";
				}
			}
		}
	}

	// Remove duplicates, convert into an array and reverse list order
	$options = array_reverse(array_filter(array_unique(explode('|', $options))));

	// For each pet option
	foreach( $options as $option ) {

		if ($option != '' ) {

			// Create a condensed version without spaces or special characters
			$option_condensed = pet_value_condensed($option);

			// Create a list
			$option_list .=    '<label>
								<input type="checkbox" class="pf-sort" data-target=".' . $option_condensed . '" checked>' .
									$option .
							'</label>';

		}

	}

	$option_list =  '<div class="grid-half space-bottom-small">
						<h3>Special Requirements</h3>
						<form>' .
							$option_list .
						'</form>
					</div>';

	// Return the list
	return $option_list;

}





/* =============================================================
	PET OPTIONS LIST
	Get a list of options for a specific pet.
 * ============================================================= */

function get_pet_options_list($pet) {

	// Define Variables
	$pet_options = '';

	foreach( $pet->options->option as $option ) {
		if ( $option == 'noCats' ) { $noCats = true; }
		if ( $option == 'noDogs' ) { $noDogs = true; }
		if ( $option == 'noKids' ) { $noKids = true; }
		if ( $option == 'specialNeeds' ) { $specialNeeds = true; }
	}

	// Create content for pet options section
	if( $noCats == true && $noDogs == true && $noKids == true ) {
		$pet_options = 'No Cats/Dogs/Kids';
	}
	else if ( $noCats == true && $noDogs == true ) {
		$pet_options = 'No Cats/Dogs';
	}
	else if ( $noCats == true && $noKids == true ) {
		$pet_options = 'No Cats/Kids';
	}
	else if ( $noDogs == true && $noKids == true ) {
		$pet_options = 'No Dogs/Kids';
	}
	else if ($noCats == true ) {
		$pet_options = 'No Cats';
	}
	else if ( $noDogs == true ) {
		$pet_options = 'No Dogs';
	}
	else if ( $noKids == true ) {
		$pet_options = 'No Kids';
	}
	if( $specialNeeds == true ){
		$pet_options .= 'Special Needs';
	}

	return $pet_options;

}





/* =============================================================
	GET ALL PETS
	Get a list of all available pets.
 * ============================================================= */

function get_all_pets($petfinder_data) {

	$pet_list = '';

	foreach ( $petfinder_data as $key => $data ) {
		$pets = $data->pets->pet;

		if ( $pets !== undefined && $pets !== null ) {
			foreach( $pets as $pet ) {

				// Define Variables
				$pet_name = get_pet_name($pet->name);
				$pet_size = get_pet_size($pet->size);
				$pet_age = get_pet_age($pet->age);
				$pet_gender = get_pet_gender($pet->sex);
				$pet_photo = get_pet_photos($pet);
				$pet_url = get_permalink() . '?view=pet-details&id=' . $pet->id . '&pet-name=' . $pet_name . '&qcAC=1';

				// Format pet options
				$pet_options = get_pet_options_list($pet);
				if ( $pet_options != '' ) {
					$pet_options = '<div class="text-small text-muted">' . $pet_options . '</div>';
				}

				// Create breed classes
				$pet_breeds_condensed = '';
				foreach( $pet->breeds->breed as $breed ) {
					$pet_breeds_condensed .= pet_value_condensed($breed) . ' ';
				}

				// Create options classes
				$pet_options_condensed = '';
				foreach( $pet->options->option as $option ) {
					$option = get_pet_option($option);
					if ( $option != '' ) {
						$pet_options_condensed .= pet_value_condensed($option) . ' ';
					}
				}

				// Create location class
				if ( stripos( $pet->name, 'local' ) === false ) {
					$pet_location = '';
					$pet_location_condensed = 'out-of-state';
				} else {
					$pet_location = '<div class="text-small text-muted">Local</div>';
					$pet_location_condensed = 'local';
				}


				// Compile pet info
				// Add $pet_options and $pet_breeds as classes and meta info
				$pet_list .=    '<div class="grid-img text-center space-bottom pf ' . pet_value_condensed($pet_age) . ' ' . pet_value_condensed($pet_gender) . ' ' . pet_value_condensed($pet_size) . ' ' . $pet_breeds_condensed . ' ' . $pet_options_condensed . $pet_location_condensed . '" data-right-height-content>
									<a href="' . $pet_url . '">' .
										$pet_photo .
										'<h3 class="no-space-top space-bottom-small">' . $pet_name . '</h3>
									</a>' .
									$pet_size . ', ' . $pet_age . ', ' . $pet_gender .
									$pet_options .
									$pet_location .
								'</div>';

			}
		}

	}

	// Return pet list
	return $pet_list;

}






/* =============================================================
	GET ONE PET
	Get and display information on a specific pet.
 * ============================================================= */

function get_one_pet($pet) {

	// Define Variables
	$pet_id = $pet->id;
	$pet_name = get_pet_name($pet->name);
	$pet_size = get_pet_size($pet->size);
	$pet_age = get_pet_age($pet->age);
	$pet_gender = get_pet_gender($pet->sex);
	$pet_photos_url = get_permalink() . '?view=pet-details&id=' . $pet_id . '&photos=all&qcAC=1';
	$pet_description = get_pet_description($pet->description);
	$pet_profile_url = get_permalink() . '?view=pet-details&id=' . $pet_id . '&qcAC=1';

	// Get list of breed(s)
	$pet_breeds = '';
	foreach( $pet->breeds->breed as $breed ) {
		$pet_breeds .= '<br>' . $breed;
	}

	// Format pet options
	$pet_options = get_pet_options_list($pet);
	if ( $pet_options != '' ) {
		$pet_options = '<p><em>' . $pet_options . '</em></p>';
	}

	if ( isset( $_GET['photos'] ) && $_GET['photos'] == 'all' ) {
		$pet_info =    '<div class="row text-center">
							<div class="grid-4 offset-1">
								<h1 class="no-space-bottom">Photos of ' . $pet_name . '</h1>
								<p><a href="' . $pet_profile_url . '">&larr; Back to ' . $pet_name . '\'s profile</a></p>' .
								get_pet_photos($pet, 'large', false, '<p>', '</p>') .
							'</div>
						</div>';
	}

	else {
		// Compile pet info
		$pet_info =    '<h1 class="text-center no-space-bottom">' . $pet_name . '</h1>
						<p class="text-center"><a href="' . get_permalink() . '">&larr; Back to all dogs</a></p>
						<div class="pet-img-main text-center" data-pet-img-main></div>
						<div class="row">
							<div class="grid-4 offset-1">
								<div class="pet-img-thumbs text-center group">' . get_pet_photos($pet, 'large', false, '<div class="pet-img-thumb"><a data-pet-img-toggle href="' . $pet_photos_url . '">', '</div>') . '</a></div>
								<div class="row">
									<div class="grid-half">
										<p>
											<strong>Size:</strong> ' . $pet_size . '<br>
											<strong>Age:</strong> ' . $pet_age . '<br>
											<strong>Gender:</strong> ' . $pet_gender . '
										</p>
									</div>
									<div class="grid-half">
										<p>
											<strong>Breed(s):</strong>' .
											$pet_breeds .
										'</p>
									</div>
								</div>' .
							$pet_options .
							'<p>
								<a class="btn adopt-toggle" data-name="' . $pet_name . ' (ID#: ' . $pet_id . ')' . '" href="' . site_url() . '/adoption-form/">Fill Out an Adoption Form</a>
							</p>' .
							$pet_description .
						'</div><div class="group"></div>';
	}

	// Return pet info
	return $pet_info;

}





/* =============================================================
	DISPLAY PETFINDER LISTINGS
	Compile lists and pet info, and display via a shortcode.
 * ============================================================= */

function display_petfinder_list($atts) {

	// Extract shortcode values
	extract(shortcode_atts(array(
		'api_key' => '',
		'shelter_id' => '',
		'count' => '25'
	), $atts));

	// Define variables
	$petfinder_list = '';

	// Display info on a specific dog
	if ( isset( $_GET['view'] ) && $_GET['view'] == 'pet-details' ) {

		// Access Petfinder Data
		$pet_id = $_GET['id'];
		$petfinder_data = get_petfinder_data($api_key, $shelter_id, '1', $pet_id, 0);

		// If the API returns without errors
		if( $petfinder_data->header->status->code == '100' ) {

			$pet = $petfinder_data->pet;

			// Compile information that you want to include
			$petfinder_list = get_one_pet($pet);

			// Update <title> element
			update_pretty_title($pet);

		}

		// If error code is returned
		else {
			$petfinder_list = '<p>There isn\'t any information currently available for this pet. Sorry!</p>';
		}

	}

	// Display a list of all available dogs
	else {

		// Access Petfinder Data
		$divisor = ceil( $count / 25 );
		$petfinder_data = array();
		for ( $loop = 0, $offset = 0; $loop < $divisor; $loop++, $offset = $offset + 25 ) {
			$petfinder_data[] = get_petfinder_data( $api_key, $shelter_id, '25', '', $offset );
		}

		// If the API returns without errors
		if( $petfinder_data[0]->header->status->code == '100' ) {

			// If there is at least one animal
			if( count( $petfinder_data[0]->pets->pet ) > 0 ) {

				$pets = $petfinder_data[2]->pets->pet;

				// Compile information that you want to include
				$petfinder_list =   '<h1 class="text-center">Our Dogs</h1>
									<div class="hide-no-js">
										<p>Your perfect companion could be just a click away. Use the filters to narrow your search, and click on a dog to learn more.</p>
									</div>
									<div class="hide-js">
										<p>Your perfect companion could be just a click away. Click on a dog to learn more.</p>
									</div>
									<div class="collapse hide-no-js" id="sort-options">

										<div class="row">' .
											get_age_list($petfinder_data) .
											get_size_list($petfinder_data) .
											get_gender_list($petfinder_data) .
										'</div>

										<div class="row">' .
											get_options_list($petfinder_data) .
											get_pet_location($petfinder_data) .
										'</div>

										<div class="row">' .
											get_breed_list($petfinder_data) .
										'</div>

									</div>
									<p>
										<a class="btn collapse-toggle" data-collapse="#sort-options" href="#">
											<svg class="icon" role="presentation"><use xlink:href="#filter"></use></svg>
											Filter Results
											<span class="collapse-text-show">+</span>
											<span class="collapse-text-hide">&ndash;</span>
										</a>
									</p>

									<div class="row" data-right-height>' .
										get_all_pets($petfinder_data) .
									'</div>';

			}

			// If no animals are available for adoption
			else {
				$petfinder_list = '<p>We don\'t have any pets available for adoption at this time. Sorry! Please check back soon.</p>';
			}
		}

		// If error code is returned
		else {
			$petfinder_list = '<p>Petfinder is having trouble sending us a list of pets at the moment. <a href="http://www.petfinder.com/pet-search?shelter_id=RI77&preview=1&sort=breed">View our adoptable dogs directly on Petfinder</a>.</p>';
		}

	}


	return $petfinder_list;

}
add_shortcode('petfinder_list','display_petfinder_list');


?>