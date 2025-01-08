<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



// Increase maximum execution time and memory limit if needed
ini_set('max_execution_time', '300');
ini_set('memory_limit', '512M');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Handle deletion if tempId is passed
    if (isset($_POST['tempId'])) {
        $tempId = $_POST['tempId'];
        $tempFilePath = __DIR__ . '/temp.json';

        if (file_exists($tempFilePath)) {
            $tempData = json_decode(file_get_contents($tempFilePath), true);

            if (isset($tempData[$tempId])) {
                // Delete related data using temp data
                $slug = $tempData[$tempId]['slug'];

                // Delete the HTML file
                $postFileName = __DIR__ . '/' . $slug . '.html';
                if (file_exists($postFileName)) {
                    unlink($postFileName);
                }

                // Delete the featured image
                $featuredImagePath = str_replace('https://dacqatar.com/blog/', __DIR__ . '/', $tempData[$tempId]['featuredImage']);
                if (file_exists($featuredImagePath)) {
                    unlink($featuredImagePath);
                }

                // Delete from timestamp.json
                $timestampFilePath = __DIR__ . '/timestamp.json';
                if (file_exists($timestampFilePath)) {
                    $timestampData = json_decode(file_get_contents($timestampFilePath), true);
                    foreach ($timestampData as $timestamp => $data) {
                        if ($data['slug'] === $slug) {
                            unset($timestampData[$timestamp]);
                            file_put_contents($timestampFilePath, json_encode($timestampData, JSON_PRETTY_PRINT));
                            break;
                        }
                    }
                }

                // Delete from tags.json
                $tagsFilePath = __DIR__ . '/tags.json';
                if (file_exists($tagsFilePath)) {
                    $tagsData = json_decode(file_get_contents($tagsFilePath), true);
                    foreach ($tagsData['hashtags'] as $tag => $posts) {
                        if (isset($posts[$slug . '.html'])) {
                            unset($tagsData['hashtags'][$tag][$slug . '.html']);
                            if (empty($tagsData['hashtags'][$tag])) {
                                unset($tagsData['hashtags'][$tag]);
                            }
                        }
                    }
                    file_put_contents($tagsFilePath, json_encode($tagsData, JSON_PRETTY_PRINT));
                }

                // Remove temp data after successful deletion
                unset($tempData[$tempId]);
                file_put_contents($tempFilePath, json_encode($tempData, JSON_PRETTY_PRINT));
            }
        }
    }

    // Ensure all form fields are present
    $required_fields = ['title', 'content', 'focusKeyphrase', 'seoTitle', 'slug', 'metaDescription', 'tags', 'visibility', 'category'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field])) {
            die("Error: Missing $field");
        }
    }

    // Get form data
    $title = htmlspecialchars($_POST['title']);
    $content = $_POST['content'];
    $focusKeyphrase = htmlspecialchars($_POST['focusKeyphrase']);
    $seoTitle = htmlspecialchars($_POST['seoTitle']);
    $slug = htmlspecialchars($_POST['slug']);
    $metaDescription = htmlspecialchars($_POST['metaDescription']);
    $canonicalUrl = isset($_POST['canonicalUrl']) && !empty($_POST['canonicalUrl']) ? htmlspecialchars($_POST['canonicalUrl']) : $rootPath . $slug ;
    $headScriptsInput = $_POST['headSrcipts'];
    $bodyScripts = $_POST['bodySrcipts'];
    $structuredDataInput = $_POST['structuredData'];
    $otherHeadScripts = $_POST['otherHeadScripts'];
    $tags = $_POST['tags'];
    $visibility = $_POST['visibility'];
    $category = htmlspecialchars($_POST['category']); // New category field
    // New geo-location fields
    $geoRegion = htmlspecialchars($_POST['geoRegion']);
    $geoPlacename = htmlspecialchars($_POST['geoPlacename']);
    $geoPosition = htmlspecialchars($_POST['geoPosition']);
    $ICBM = htmlspecialchars($_POST['ICBM']);
    // Check if a custom timestamp was provided
    if (!empty($_POST['timestamp'])) {
        // Use the custom timestamp provided by the user
        $publishDateTime = date('c', strtotime($_POST['timestamp']));
        $formattedPublishDate = date('F j, Y', strtotime($_POST['timestamp'])); // Format for display
    } else {
        // Use the current date and time as the default
        $publishDateTime = date('c');
        $formattedPublishDate = date('F j, Y'); // Default formatting
    }

    // Extract the first line from the content
    $plainTextContent = strip_tags($content);
    $firstLine = substr($plainTextContent, 0, 100);
    $wordCount = str_word_count($plainTextContent); // Calculate word count

    // Handle image upload
    $targetDir = "uploads/";
    $featuredImage = "";

    // Check if the post is being edited
    $isEditing = isset($_POST['isEditing']) && $_POST['isEditing'] === 'true';

    if (!empty($_FILES['featuredImage']['name'])) {
        // If a new image is uploaded, process the image
        $targetFile = $targetDir . basename($_FILES["featuredImage"]["name"]);
        if (move_uploaded_file($_FILES["featuredImage"]["tmp_name"], $targetFile)) {
            $featuredImage = $targetFile;
        } else {
            echo"<script type='text/javascript'>alert('Invalid request method.');</script>";
             //die("Error: Unable to upload image.");
        }
    } else {
        // If no new image is uploaded and this is an edit, retain the existing image
        if ($isEditing) {
            $timestampFilePath = __DIR__ . '/timestamp.json';
            if (file_exists($timestampFilePath)) {
                $timestampData = json_decode(file_get_contents($timestampFilePath), true);
                foreach ($timestampData as $timestamp => $data) {
                    if ($data['slug'] === $slug) {
                        $featuredImage = str_replace($data['featuredImage']);
                        break;
                    }
                }
            }
        }
    }

    // If the featured image is still empty, ensure it's not accidentally cleared
    if (empty($featuredImage)) {
        $timestampFilePath = __DIR__ . '/timestamp.json';
            if (file_exists($timestampFilePath)) {
                $timestampData = json_decode(file_get_contents($timestampFilePath), true);
                foreach ($timestampData as $timestamp => $data) {
                    if ($data['slug'] === $slug) {
                        $featuredImage = str_replace($rootPath, '', $data['featuredImage']);
                        break;
                    }
                }
            }
    }

    // Get form data
    $category = htmlspecialchars($_POST['category']);

    // Load existing categories
    $categoriesFilePath = __DIR__ . '/categories.json';
    if (file_exists($categoriesFilePath)) {
        $categoriesData = json_decode(file_get_contents($categoriesFilePath), true);

        // If the category doesn't exist, add it to categories.json
        if (!in_array($category, $categoriesData['categories'])) {
            $categoriesData['categories'][] = $category;
            file_put_contents($categoriesFilePath, json_encode($categoriesData, JSON_PRETTY_PRINT));
        }
    }

    // User-defined global variables
    $domainName = 'https://dacqatar.com/';
    $rootPath = 'https://dacqatar.com/blog/';
    $language = 'en_US';
    $openGraphType = 'article';
    $publisherUrl = 'https://www.facebook.com/dubaiadvertisingco';
    $publisherName = 'DAC QATAR';
    $publisherTwitterId = '@twitterid';
    $publisherLogo = 'https://dacqatar.com/assets/images/Dac%20LOGO%20black.webp';
    $publisherTagline = 'Empowering brands to connect with their ideal audience - DAC: Your gateway to advertising success';
    $favioconLink = 'https://dacqatar.com/assets/images/favicon.webp';
    $blogHome = 'https://dacqatar.com/blog/index.html';
    $facebookProfileLink = 'https://www.facebook.com/dubaiadvertisingco';
    $instagramProfileLink = 'https://www.instagram.com/dac_qatar/';
    $threadsProfileLink = '';
    $twitterProfileLink = '';
    $linkedinProfileLink = '';
    $whatsappProfileLink = 'https://wa.me/+97466637095';
    $youtubeProfileLink = '';
    $publisherAddress = 'Dubai Advertising Company W.L.L, Building No. 65, Street No. 3083, Zone No. 91, Birkat Al Awamer Doha, Qatar';
    $publisherMobile = '+97444684262';
    $publisherEmail = 'qatardac@gmail.com';
    $privacyPolicy = 'https://dacqatar.com/privacy-policy.html';
    $termsAndCondition = 'https://dacqatar.com/terms-and-condition.html';
    $siteMap = 'https://dacqatar.com/sitemap.html';

    // Processed variables
    // $canonicalUrl = $rootPath . $slug . '.html';
    $CurrentDateTime = $formattedPublishDate;
    $featuredImageUrl = $rootPath . $featuredImage;
    $logoImageUrl = $rootPath . $publisherLogo;
    // $formattedPublishDate = date('F j, Y');
    $blogHomeUrl = $domainName . $blogHome;
    $privacyPolicyUrl = $domainName . $privacyPolicy;
    $termsAndConditionUrl = $domainName . $termsAndCondition;
    $siteMapUrl = $domainName . $siteMap;
    $categoryLinks = '<a href="categories.html?category=' . urlencode($category) . '">' . htmlspecialchars($category) . '</a>';
    $headScriptsInput = isset($_POST['headSrcipts']) ? $_POST['headSrcipts'] : ''; // Check if the field is set
    $structuredDataInput = isset($_POST['structuredData']) ? $_POST['structuredData'] : ''; // Check if the field is set

    

    // Read the existing tags.json file
    $tagsFilePath = __DIR__ . "/tags.json";
    $tagsData = file_exists($tagsFilePath) ? json_decode(file_get_contents($tagsFilePath), true) : ["hashtags" => []];

    // Process each tag and update the tags.json structure
    $tagsArray = explode(',', $tags);
    
    $formattedTagsForJson = array_map(function($tag) {
        $tag = trim($tag);
        if (strpos($tag, '#') !== 0) {
            $tag = '#' . $tag;
        }
        return $tag;
    }, $tagsArray);
    $formattedTagsString = implode(',', $formattedTagsForJson);




            // Check if robotsMeta is present in the form submission
            if (isset($_POST['robotsMeta'])) {
                $robotsMeta = htmlspecialchars($_POST['robotsMeta']);
            } else {
                // Default to 'index, follow' if not provided
                $robotsMeta = 'index, follow';
            }




    if (!empty($headScriptsInput)) {
        // If the structuredDataInput is not empty, use the user's input
        $headScripts = $headScriptsInput;
    } else {
$headScriptsTemplate = '
        <title>$title</title>
        <meta name="description" content="$metaDescription" />
        <meta name="robots" content="$robotsMeta" />
        <meta name="geo.region" content="$geoRegion" />
        <meta name="geo.placename" content="$geoPlacename" />
        <meta name="geo.position" content="$geoPosition" />
        <meta name="ICBM" content="$ICBM" />
        <link rel="shortcut icon" type="image/jpg" href="$favioconLink" />
        <link rel="canonical" href="$canonicalUrl" />
        <meta property="og:locale" content="$language" />
        <meta property="og:type" content="$openGraphType" />
        <meta property="og:title" content="$seoTitle" />
        <meta property="og:description" content="$metaDescription" />
        <meta property="og:url" content="$canonicalUrl" />
        <meta property="article:publisher" content="$publisherUrl" />
        <meta property="article:published_time" content="$publishDateTime" />
        <meta name="author" content="$publisherName" />
        <meta property="og:image:type" content="image/jpeg" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:creator" content="$publisherTwitterId" />
        <meta name="twitter:site" content="$publisherTwitterId" />
        <meta name="twitter:label1" content="Written by" />
        <meta name="twitter:data1" content="$publisherName" />
        <meta name="twitter:label2" content="Est. reading time" />
        <meta name="twitter:data2" content="4 minutes" />
        ';

                
        // Replace the placeholders with actual PHP variables
        $headScripts = str_replace(
            ['$title', '$robotsMeta', '$geoRegion', '$geoPlacename', '$geoPosition', '$ICBM', '$favioconLink', '$metaDescription', '$canonicalUrl', '$language', '$openGraphType', '$seoTitle', '$metaDescription', '$canonicalUrl', '$publisherUrl', '$CurrentDateTime', '$publisherName', '$publisherTwitterId' , '$publishDateTime'],
            [$title, $robotsMeta, $geoRegion, $geoPlacename, $geoPosition, $ICBM, $favioconLink, $metaDescription, $canonicalUrl, $language, $openGraphType, $seoTitle, $metaDescription, $canonicalUrl, $publisherUrl, $CurrentDateTime, $publisherName, $publisherTwitterId, $publishDateTime],
            $headScriptsTemplate
        );

    }




    if (!empty($structuredDataInput)) {
        // If the structuredDataInput is not empty, use the user's input
        $structuredData = $structuredDataInput;
    } else {
        $structuredDataTemplate = '
        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@graph": [
                    {
                        "@type": "$openGraphType",
                        "@id": "$canonicalUrl/#$openGraphType",
                        "isPartOf": {
                            "@id": "$canonicalUrl/"
                        },
                        "author": {
                            "name": "$publisherName",
                            "@id": "$blogHomeUrl"
                        },
                        "headline": "$title",
                        "datePublished": "$publishDateTime",
                        "mainEntityOfPage": {
                            "@id": "$canonicalUrl/"
                        },
                        "wordCount": "$wordCount",
                        "commentCount": 0,
                        "publisher": {
                            "@id": "$blogHomeUrl"
                        },
                        "image": {
                            "@id": "$canonicalUrl/#primaryimage"
                        },
                        "thumbnailUrl": "$featuredImageUrl",
                        "keywords": [
                            $formattedTagsString
                        ],
                        "articleSection": [
                            "Blog"
                        ],
                        "inLanguage": "$language"
                    },
                    {
                        "@type": "WebPage",
                        "@id": "$canonicalUrl/",
                        "url": "$canonicalUrl/",
                        "name": "$seoTitle",
                        "isPartOf": {
                            "@id": "$blogHomeUrl"
                        },
                        "primaryImageOfPage": {
                            "@id": "$canonicalUrl/#primaryimage"
                        },
                        "image": {
                            "@id": "$canonicalUrl/#primaryimage"
                        },
                        "thumbnailUrl": "$featuredImageUrl",
                        "datePublished": "$publishDateTime",
                        "description": "$metaDescription.",
                        "breadcrumb": {
                            "@id": "$canonicalUrl/#breadcrumb"
                        },
                        "inLanguage": "$language",
                        "potentialAction": [
                            {
                                "@type": "ReadAction",
                                "target": [
                                    "$canonicalUrl/"
                                ]
                            }
                        ]
                    },
                    {
                        "@type": "ImageObject",
                        "inLanguage": "$language",
                        "@id": "$canonicalUrl/#primaryimage",
                        "url": "$featuredImageUrl",
                        "contentUrl": "$featuredImageUrl",
                        "caption": "$title"
                    },
                    {
                        "@type": "BreadcrumbList",
                        "@id": "$canonicalUrl/#breadcrumb",
                        "itemListElement": [
                            {
                                "@type": "ListItem",
                                "position": 1,
                                "name": "Home",
                                "item": "$blogHomeUrl"
                            },
                            {
                                "@type": "ListItem",
                                "position": 2,
                                "name": "$title"
                            }
                        ]
                    },
                    {
                        "@type": "WebSite",
                        "@id": "$blogHomeUrl/#website",
                        "url": "$blogHomeUrl/",
                        "name": "$publisherName",
                        "description": "$publisherTagline",
                        "publisher": {
                            "@id": "$blogHomeUrl/#organization"
                        },
                        "inLanguage": "$language"
                    },
                    {
                        "@type": "Organization",
                        "@id": "$blogHomeUrl/#organization",
                        "name": "$publisherName",
                        "alternateName": "$publisherName",
                        "url": "$blogHomeUrl",
                        "logo": {
                            "@type": "ImageObject",
                            "inLanguage": "$language",
                            "@id": "$blogHomeUrl",
                            "url": "$logoImageUrl",
                            "contentUrl": "$logoImageUrl",
                            "caption": "$publisherName"
                        },
                        "image": {
                            "@id": "$blogHomeUrl"
                        },
                        "sameAs": [
                            "$facebookProfileLink",
                            "$threadsProfileLink",
                            "$instagramProfileLink",
                            "$linkedinProfileLink"
                        ]
                    },
                    {
                        "@type": "Person",
                        "@id": "$blogHomeUrl",
                        "name": "$publisherName"
                    }
                ]
            }
            </script>
        ';
 
        
        
        // Replace the placeholders with actual PHP variables
        $structuredData = str_replace(
            ['$wordCount', '$openGraphType', '$canonicalUrl', '$publisherName', '$blogHomeUrl', '$title', '$publishDateTime', '$featuredImageUrl', '$formattedTagsString', '$language', '$seoTitle', '$metaDescription', '$publisherTagline', '$logoImageUrl', '$facebookProfileLink', '$threadsProfileLink', '$instagramProfileLink', '$linkedinProfileLink'],
            [$wordCount, $openGraphType, $canonicalUrl, $publisherName, $blogHomeUrl, $title, $publishDateTime, $featuredImageUrl, $formattedTagsString, $language, $seoTitle, $metaDescription, $publisherTagline, $logoImageUrl, $facebookProfileLink, $threadsProfileLink, $instagramProfileLink, $linkedinProfileLink],
            $structuredDataTemplate
        );

    }

    $postFileName = $slug . ".html"; // The name of the HTML file being created
    foreach ($tagsArray as $tag) {
        $tag = trim($tag); // Trim any whitespace around the tag
        if (!isset($tagsData["hashtags"][$tag])) {
            $tagsData["hashtags"][$tag] = [];
        }

        // Append or update the data under the filename
        $tagsData["hashtags"][$tag][$postFileName] = [
            "title" => $title,
            "featuredImage" => $featuredImageUrl,
            "url" => $canonicalUrl,
            "category" => $category, // Include category in tags.json
            "visibility" => $visibility
        ];
    }

    // Remove tags no longer associated with the post
    foreach ($tagsData['hashtags'] as $tag => $posts) {
        if (!in_array($tag, $tagsArray)) {
            unset($tagsData['hashtags'][$tag][$postFileName]);
            if (empty($tagsData['hashtags'][$tag])) {
                unset($tagsData['hashtags'][$tag]);
            }
        }
    }

    // Write the updated data back to tags.json
    if (file_put_contents($tagsFilePath, json_encode($tagsData, JSON_PRETTY_PRINT)) === false) {
        die("Error: Unable to update tags.json.");
    }

    // Handle timestamp.json for recent posts
    $timestampFilePath = __DIR__ . "/timestamp.json";
    $timestampData = file_exists($timestampFilePath) ? json_decode(file_get_contents($timestampFilePath), true) : [];

    // Check if this post already exists in timestamp.json (by slug or URL)
    $existingTimestamp = null;
    foreach ($timestampData as $timestamp => $data) {
        if ($data['slug'] === $slug) {
            $existingTimestamp = $timestamp;
            break;
        }
    }

    // If the post exists, remove the old entry and delete associated files
    if ($existingTimestamp) {
        unset($timestampData[$existingTimestamp]);
        $existingPostFile = __DIR__ . "/" . $slug . ".html";
        if (file_exists($existingPostFile)) {
            unlink($existingPostFile); // Delete the old HTML file
        }

        // If a new image is uploaded, delete the old one
        if (!empty($_FILES['featuredImage']['name'])) {
            $oldImage = str_replace($rootPath, '', $timestampData[$existingTimestamp]['featuredImage']);
            $oldImagePath = __DIR__ . "/" . $oldImage;
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath); // Delete the old featured image
            }
        }
    }

// Capture the robotsMeta value from the form submission
$robotsMeta = isset($_POST['robotsMetaInput']) ? $_POST['robotsMetaInput'] : 'index, follow';

    $geoRegion = htmlspecialchars($_POST['geoRegion']);
    $geoPlacename = htmlspecialchars($_POST['geoPlacename']);
    $geoPosition = htmlspecialchars($_POST['geoPosition']);
    $ICBM = htmlspecialchars($_POST['ICBM']);
    
    $timestampData[$publishDateTime] = [
        "title" => $title,
        "featuredImage" => $featuredImage,
        "url" => $canonicalUrl,
        "firstLine" => $firstLine,
        "content" => $content,
        "focusKeyphrase" => $focusKeyphrase,
        "seoTitle" => $seoTitle,
        "slug" => $slug,
        "metaDescription" => $metaDescription,
        "tags" => $tags,
        "visibility" => $visibility,
        "category" => $category,
        "robotsMeta" => $robotsMeta, // Ensure this is saved
        "geoRegion" => $geoRegion,
        "geoPlacename" => $geoPlacename,
        "geoPosition" => $geoPosition,
        "ICBM" => $ICBM,
        "canonicalUrl" => $canonicalUrl, // Save canonical URL in timestamp.json
        "headScripts" => $headScripts,   // New key for head scripts
        "otherHeadScripts" => $otherHeadScripts,
        "bodyScripts" => $bodyScripts,    // New key for body scripts
        "structuredData" => $structuredData,
        "timestamp" => $publishDateTime
    ];

    // Write the updated data back to timestamp.json
    if (file_put_contents($timestampFilePath, json_encode($timestampData, JSON_PRETTY_PRINT)) === false) {
        die("Error: Unable to update timestamp.json.");
    }

    // Generate hashtag links
    $tagLinks = array_map(function($tag) {
        return '<a href="hashtagposts.html?tag=' . urlencode(trim($tag)) . '"> ' . htmlspecialchars(trim($tag)) . '</a>';
    }, $tagsArray);
    $tagLinksString = implode(', ', $tagLinks);

    // Create category links
    // $categoryLinks = '<a href="categories.html?category=blog">Blog</a>, <a href="categories.html?category=case%20study">Case Study</a>';

    if ($visibility === 'public') {
        // Generate hashtag links
        $tagLinks = array_map(function($tag) {
            return '<a href="hashtagposts.html?tag=' . urlencode(trim($tag)) . '"> ' . htmlspecialchars(trim($tag)) . '</a>';
        }, $tagsArray);
        $tagLinksString = implode(', ', $tagLinks);
    

        // Check if robotsMeta is present in the form submission
        if (isset($_POST['robotsMeta'])) {
            $robotsMeta = htmlspecialchars($_POST['robotsMeta']);
        } else {
            // Default to 'index, follow' if not provided
            $robotsMeta = 'index, follow';
        }
        
        // Create the blog post content with updated styling and hashtag links
        $blogPostContent = <<<HTML
        <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        $headScripts
        $structuredData
        $otherHeadScripts
        
        <link rel="stylesheet" href="blog.css">
        <link rel="stylesheet" href="stylesheet.css">
        <link rel="stylesheet" href="../assets/css/style.css">
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="../assets/css/animate.min.css">
        <link rel="stylesheet" href="../assets/css/fontawesome.min.css">
        <link rel="stylesheet" href="../assets/css/magnific-popup.min.css">
        <link rel="stylesheet" href="../assets/css/nice-select.min.css">
        <link rel="stylesheet" href="../assets/css/jquery-ui.min.css">
        <link rel="stylesheet" href="../assets/css/flaticon.min.css">
        <link rel="stylesheet" href="../assets/css/slick.min.css">
        <link rel="stylesheet" href="../assets/css/style.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">

        <script src="recentposts.js"></script> <!-- Add this line to include the recentposts.js script -->
    </head>
    <body>


    <div class="page-wrapper">


<!--Form Back Drop-->
<div class="form-back-drop"></div>

<!-- Hidden Sidebar -->
<section class="hidden-bar">
    <div class="inner-box text-center">
        <div class="cross-icon"><span class="fa fa-times"></span></div>
        <div class="title">
            <h4>Get Appointment</h4>
        </div>

        <!--Appointment Form-->
        <div class="appointment-form">
            <form method="post" action="../sidebar.php">
                <div class="form-group">
                    <input id="name" type="text" name="name" value="" placeholder="Name" required>
                </div>
                <div class="form-group">
                    <input id="email" type="email" name="email" value="" placeholder="Email Address" required>
                </div>
                <div class="form-group">
                    <textarea id="message" name="message" placeholder="Message" rows="5"></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="theme-btn">Submit now</button>
                </div>
            </form>
        </div>

        <!--Social Icons-->
        <div class="social-style-one">
            <!-- <a href="#"><i class="fab fa-twitter"></i></a> -->
            <a target="_blank" href="https://www.facebook.com/dubaiadvertisingco"><i
                    class="fab fa-facebook-f"></i></a>
            <a target="_blank" href="https://www.instagram.com/dac_qatar/"><i class="fab fa-instagram"></i></a>
            <a target="_blank" href="https://wa.me/+97466637095"><i class="fab fa-whatsapp"></i></a>
        </div>
    </div>
</section>
<!--End Hidden Sidebar -->

<!-- navbar start -->
<nav class="navbar style-one rel navbar-area navbar-expand-lg py-20">
    <div class="container container-1570">
        <div class="responsive-mobile-menu">
            <button class="menu toggle-btn d-block d-lg-none" data-target="#Iitechie_main_menu"
                aria-expanded="false" aria-label="Toggle navigation">
                <span class="icon-left"></span>
                <span class="icon-right"></span>
            </button>
        </div>
        <div class="logo">
            <a href="../index.html"><img src="../assets/images/Dac LOGO black.webp" alt="img"></a>
        </div>
        <div class="nav-right-part nav-right-part-mobile">
            <a class="search-bar-btn" href="#">
                <i class="far fa-search"></i>
            </a>
        </div>
        <div class="collapse navbar-collapse" id="Iitechie_main_menu">
            <ul class="navbar-nav menu-open text-lg-end">
                <li>
                    <a href="../index.html">Home</a>
                </li>
                <li><a href="../about.html">about</a></li>
                <li><a href="../services.html">Services</a></li>
                <li><a href="../blog/index.html">Blog</a></li>
                <li><a href="../contact.html">Contact Us</a></li>
                <li>
                    <a href="../portfolio.html">Portfolio</a>
                </li>
            </ul>
            </li>
            </ul>
        </div>
        <div class="nav-right-part nav-right-part-desktop">
            <a target="_blank" href="../assets/DAC-Profile.pdf" class="theme-btn style-two">Get Portfolio <i
                    class="far fa-long-arrow-right"></i></a>
            <div class="menu-sidebar">
                <button>
                    <i class="far fa-ellipsis-h"></i>
                    <i class="far fa-ellipsis-h"></i>
                    <i class="far fa-ellipsis-h"></i>
                </button>
            </div>
        </div>
    </div>
</nav>
<!-- navbar end -->


<div class="row base_container">
        <div class="col-xl-9 col-lg-9 col-md-9 col-sm-12 col-12 base_container_col1">
            <div class="container">
                <img src="$featuredImage" class="featured-image" alt="Featured Image">
                <h1 class="post-title">$title</h1>
                <p class="post-meta">By $publisherName | Published on $formattedPublishDate</p>
                <div class="post-content">$content</div>
                <p class="post-tags">Tags: $tagLinksString</p>
                <p class="post-categories">Category: $categoryLinks</p>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-12 base_container_col2">
            <h3>Recent posts:</h3>
                <div class="recentpost_card">
                    <h5><!--title of the latest post title of the latest post--> </h5>
                    <img src="url to featured image" alt="">
                    <p><!-- first line of the blogpost appears here--></p>
                    <a href="">Read more</a>
                </div>
                <!-- recent posts cards appear here like this -->
        </div>
    </div>








<!-- footer area start -->
<footer class="footer-area pt-80">
    <div class="container">
        <div class="row justify-content-between">
            <div class="col-xl-4 col-lg-5 col-md-6">
                <div class="widget widget_about wow fadeInUp delay-0-2s">
                    <div class="footer-logo mb-25">
                        <a href="../index.html"><img style="width: 30%;"
                                src="../assets/images/dac logo round.webp" alt="Logo"></a>
                    </div>
                    <p>"Empowering brands to connect with their ideal audience - DAC: Your gateway to
                        advertising success."</p>
                    <div class="social-style-two mt-15">
                        <a target="_blank" href="https://www.facebook.com/dubaiadvertisingco"><i
                                class="fab fa-facebook-f"></i></a>
                        <!-- <a href="#"><i class="fab fa-twitter"></i></a> -->
                        <a target="_blank" href="https://wa.me/+97466637095"><i class="fab fa-whatsapp"></i></a>
                        <a target="_blank" href="https://www.instagram.com/dac_qatar/"><i
                                class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="widget widget_nav_menu wow fadeInUp delay-0-4s">
                    <h4 class="widget-title">Useful Links</h4>
                    <ul>
                        <li><a href="../index.html">Home</a></li>
                        <li><a href="../privacy-policy.html">Privacy Policy</a></li>
                        <li><a href="../about.html">About</a></li>
                        <li><a href="../terms-and-conditions.html">Terms and Conditions</a></li>
                        <li><a href="../services.html">Services</a></li>
                        <li><a href="../blog/index.html">Blog</a></li>
                        <li><a href="">Careers</a></li>
                        <li><a href="../contact.html">Contact Us</a></li>

                    </ul>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="widget widget_contact_info wow fadeInUp delay-0-6s">
                    <h4 class="widget-title">Contact</h4>
                    <p>Need More Audience!! <br> Let's Work Together?</p>
                    <ul>
                        <li style="font-size: 80%;"><i class="far fa-map-marker-alt"></i>Dubai Advertising
                            Company W.L.L, Building No. 65, Street No. 3083, Zone No. 91, Birkat Al Awamer Doha,
                            Qatar</li>
                        <li style="font-size: 80%;"><i class="far fa-envelope"></i> <a
                                href="mailto:qatardac@gmail.com">qatardac@gmail.com</a></li>
                        <li style="font-size: 80%;"><i class="far fa-phone"></i> <a
                                href="calto:+974 4468 4262">+974 4468 4262</a>
                        </li>
                        <li style="font-size: 80%;"><i class="far fa-phone"></i> <a
                                href="calto:+974 665 66429">+974 665 66429 / 666 370 95</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-bottom mt-15 pt-25 pb-10">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <div class="copyright-text text-center text-lg-start">
                        <p><a href="../index.html">Dubai Advertising Company</a> © Copyright 2025, Al Right
                            Reserved &nbsp; &nbsp; &nbsp; Designed and Developed by: <a target="_blank"
                                href="https://illforddigital.com/">Illford Digital</a></p>
                    </div>
                </div>
                <!-- <div class="col-lg-6">
                    <div class="payment-method-image mb-15 text-center text-lg-end">
                        <a href="#"><img src="assets/images/footer/payment-method.webp" alt="Payment Method"></a>
                    </div>
                </div> -->
            </div>

            <!-- back to top area start -->
            <div class="back-to-top">
                <span class="back-top"><i class="fa fa-angle-up"></i></span>
            </div>
            <!-- back to top area end -->
        </div>
    </div>
</footer>
<!-- footer area end -->

</div>




    


    <!-- <div class="floating_btn">
        <a target="_blank" href="https://wa.me/+97466637095" style="text-decoration: none;">
            <div class="contact_icon">
                <i class="fa fa-whatsapp my-float"></i>
            </div>
        </a>
        <p class="text_icon">Talk to us?</p>
    </div> -->
    
    
    <script src="recentposts.js"></script>
    
        <!-- all plugins here -->
        <script src="../assets/js/jquery.min.js"></script>
        <script src="../assets/js/bootstrap.min.js"></script>
        <script src="../assets/js/isotope.min.js"></script>
        <script src="../assets/js/appear.min.js"></script>
        <script src="../assets/js/imageload.min.js"></script>
        <script src="../assets/js/jquery-ui.min.js"></script>
        <script src="../assets/js/circle-progress.min.js"></script>
        <script src="../assets/js/jquery.magnific-popup.min.js"></script>
        <script src="../assets/js/jquery.nice-select.min.js"></script>
        <script src="../assets/js/skill.bars.jquery.min.js"></script>
        <script src="../assets/js/slick.min.js"></script>
        <script src="../assets/js/wow.min.js"></script>

    <!-- main js  -->
    <script src="../assets/js/main.js"></script>

    $bodyScripts
    </body>
    </html>
HTML;


    // Save the blog post content to a file in the root directory
    $postFileName = __DIR__ . "/" . $slug . ".html";
    if (file_put_contents($postFileName, $blogPostContent) === false) {
        die("Error: Unable to save the blog post.");
    }

    echo "<script>alert('Post published successfully!'); window.location.href = 'admin.html';</script>";
} else {
    echo "<script>alert('Post saved as private'); window.location.href = 'admin.html';</script>";
}


    // Save the blog post content to a file in the root directory
    $postFileName = __DIR__ . "/" . $slug . ".html";
    if (file_put_contents($postFileName, $blogPostContent) === false) {
        die("Error: Unable to save the blog post.");
    }

    echo "<script>alert('Post published successfully!'); window.location.href = 'admin.html';</script>";
} else {
    echo "<script>alert('Error: Invalid request method.'); window.location.href = 'admin.html';</script>";
}



include_once('clear_temp_json.php');
include_once('clear_temp_json.php');
?>
