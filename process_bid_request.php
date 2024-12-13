<?php

// Read the incoming JSON data from the request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if the data was decoded correctly
if (json_last_error() !== JSON_ERROR_NONE) {
    die('Invalid JSON input');
}

// Extract bid request and campaigns from the incoming data
$bidRequest = $data['bidRequest'];
$campaigns = $data['campaigns'];
function selectBestCampaign($bidRequest, $campaigns) {
    $selectedCampaign = null;
    $highestBid = 0;
    foreach ($campaigns as $campaign) {
        if (strpos($campaign['hs_os'], $bidRequest['device']['os']) === false) {
            echo "Device OS not compatible: " . $campaign['hs_os'] . " vs " . $bidRequest['device']['os'] . "\n";
            continue;
        }
        if (!empty($campaign['country']) && $campaign['country'] !== $bidRequest['device']['geo']['country']) {
            echo "Country not matching: " . $campaign['country'] . " vs " . $bidRequest['device']['geo']['country'] . "\n";
            continue;
        }
        if (!empty($campaign['city']) && $campaign['city'] !== $bidRequest['device']['geo']['city']) {
            echo "City not matching: " . $campaign['city'] . " vs " . $bidRequest['device']['geo']['city'] . "\n";
            continue;
        }
        if ($campaign['price'] < $bidRequest['imp'][0]['bidfloor']) {
            echo "Bid floor not met: " . $campaign['price'] . " vs " . $bidRequest['imp'][0]['bidfloor'] . "\n";
            continue;
        }
        if ($campaign['price'] > $highestBid) {
            $highestBid = $campaign['price'];
            $selectedCampaign = $campaign;
        }
    }

    return $selectedCampaign;
}
$selectedCampaign = selectBestCampaign($bidRequest, $campaigns);
if ($selectedCampaign) {
    $response = [
        'id' => $bidRequest['id'],
        'seatbid' => [
            [
                'bid' => [
                    [
                        'id' => $bidRequest['imp'][0]['id'],
                        'impid' => $bidRequest['imp'][0]['id'],
                        'price' => $selectedCampaign['price'],
                        'adid' => $selectedCampaign['code'],
                        'adm' => $selectedCampaign['htmltag'],
                        'crid' => $selectedCampaign['creative_id'],
                        'w' => $bidRequest['imp'][0]['banner']['w'],
                        'h' => $bidRequest['imp'][0]['banner']['h'],
                        'ext' => [
                            'advertiser' => $selectedCampaign['advertiser'],
                            'campaignname' => $selectedCampaign['campaignname'],
                            'image_url' => $selectedCampaign['image_url'],
                            'landing_page_url' => $selectedCampaign['url']
                        ]
                    ]
                ]
            ]
        ],
        'cur' => $bidRequest['cur'][0]
    ];
} else {
    $response = [
        'id' => $bidRequest['id'],
        'seatbid' => [],
        'cur' => $bidRequest['cur'][0]
    ];
}

header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);
