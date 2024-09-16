<?php
error_reporting(0);
$list_query = array_filter(@explode("\n", str_replace(array("\r", " "), "", @file_get_contents(readline("[?] List Query       ")))));
echo "[*] Total Query : ".count($list_query)."\n";
for ($i = 0; $i < count($list_query); $i++) {
    $c = $i + 1;
    echo "\n[$c]\n";
    $auth = get_auth($list_query[$i]);
    echo "[*] Get Auth : ";
    if($auth){
        echo "success\n";
        $task = get_task($auth);
        echo "[*] Get Task : ";
        if($task){
            echo "success\n\n";
            for ($a = 0; $a < count($task); $a++) {
                $ex = explode("*", $task[$a]);
                $detail = get_detail_task($auth, $ex[0]);
                echo "[-] ".$ex[1]." [$detail]\n";
                echo "\t[>] Start Task  : ".start_task($auth, $detail)."\n";
                echo "\t[>] Verify Task : ".verify_task($auth, $detail)."\n";
                $check = checkpoint($auth, $detail);
                echo "\t[>] Wait Time   : ".$check[1]." second\n";
                if($check[1] < 0){
                    continue;
                }
                sleep($check[1]);
                echo "\t[>] Solve Task  : ".solve_task($auth, $check[0])."\n";
            }
        }
        else{
            echo "failed\n\n";
        }
    }
    else{
        echo "failed\n\n";
    }
}

function get_auth($query){
    $decoded = urldecode($query);
    parse_str($decoded, $parsed_array);
    $json = json_encode($parsed_array);
    $user_data = json_decode($parsed_array['user'], true);
    $decode = json_decode($json, true);
    $auth_date = $decode['auth_date'];
    $hash = $decode['hash'];
    $chat_instance = $decode['chat_instance'];
    $user_id = $user_data['id'];
    $first_name = $user_data['first_name'];
    $last_name = $user_data['last_name'];
    $username = $user_data['username'];
    $language_code = $user_data['language_code'];
    $curl = curl(false, '[{"operationName":"MutationTelegramUserLogin","variables":{"webAppData":{"auth_date":'.$auth_date.',"hash":"'.$hash.'","query_id":"","checkDataString":"auth_date='.$auth_date.'\\nchat_instance='.$chat_instance.'\\nchat_type=private\\nuser={\\"id\\":'.$user_id.',\\"first_name\\":\\"'.$first_name.'\\",\\"last_name\\":'.$last_name.'\\"\\",\\"username\\":\\"'.$username.'\\",\\"language_code\\":\\"'.$language_code.'\\"}","user":{"id":'.$user_id.',"allows_write_to_pm":true,"first_name":"'.$first_name.'","last_name":"'.$last_name.'","username":"'.$username.'","language_code":"'.$language_code.'","version":"7.4","platform":"android"}}},"query":"mutation MutationTelegramUserLogin($webAppData: TelegramWebAppDataInput!, $referralCode: String) {\\n  telegramUserLogin(webAppData: $webAppData, referralCode: $referralCode) {\\n    access_token\\n    __typename\\n  }\\n}"}]')[0]['data']['telegramUserLogin']['access_token'];
    return $curl;
}

function get_task($auth){
    $curl = curl($auth, '[{"operationName":"QueryVideoAdTask","variables":{},"query":"query QueryVideoAdTask {\\n  videoAdTask {\\n    currentRewardAmountCoins\\n    currentRewardAmountSpinEnergy\\n    rewardAmountCoins\\n    rewardAmountSpinEnergy\\n    status\\n    __typename\\n  }\\n}"},{"operationName":"getSocialTask","variables":{},"query":"query getSocialTask {\\n  telegramStorySocialTaskLastTask {\\n    id\\n    status\\n    createdAt\\n    token\\n    nextCreateAvailableAt\\n    __typename\\n  }\\n}"},{"operationName":"CampaignLists","variables":{},"query":"fragment FragmentCampaign on CampaignOutput {\\n  id\\n  type\\n  status\\n  backgroundImageUrl\\n  campaignUserParticipationId\\n  completedTotalTasksAmount\\n  description\\n  endDate\\n  iconUrl\\n  isStarted\\n  name\\n  completionReward {\\n    spinEnergyReward\\n    coinsReward\\n    claimedAt\\n    id\\n    __typename\\n  }\\n  totalRewardsPool\\n  totalTasksAmount\\n  collectedRewardsAmount\\n  penaltyAmount\\n  penaltySpinEnergyAmount\\n  collectedSpinEnergyRewardsAmount\\n  totalSpinEnergyRewardsPool\\n  __typename\\n}\\n\\nquery CampaignLists {\\n  campaignLists {\\n    special {\\n      ...FragmentCampaign\\n      __typename\\n    }\\n    normal {\\n      ...FragmentCampaign\\n      __typename\\n    }\\n    archivedCount\\n    __typename\\n  }\\n}"}]')[2]['data']['campaignLists'];
    $base = array_keys($curl);
    for ($i = 0; $i < 2; $i++) {
        $get = $curl[$base[$i]];
        for ($j = 0; $j < count($get); $j++) {
            $list[] = $get[$j]['id']."*".$get[$j]['name'];
        }
    }
    return $list;
}

function get_detail_task($auth, $id){
    $curl = curl($auth, '[{"operationName":"GetTasksList","variables":{"campaignId":"'.$id.'"},"query":"fragment FragmentCampaignTask on CampaignTaskOutput {\\n  id\\n  name\\n  description\\n  status\\n  type\\n  position\\n  buttonText\\n  coinsRewardAmount\\n  spinEnergyRewardAmount\\n  link\\n  userTaskId\\n  isRequired\\n  iconUrl\\n  taskVerificationType\\n  verificationAvailableAt\\n  shouldUseVpn\\n  isLinkInternal\\n  quiz {\\n    id\\n    question\\n    answers\\n    __typename\\n  }\\n  __typename\\n}\\n\\nquery GetTasksList($campaignId: String!) {\\n  campaignTasks(campaignConfigId: $campaignId) {\\n    ...FragmentCampaignTask\\n    __typename\\n  }\\n}"}]')[0]['data']['campaignTasks'][0]['id'];
    return $curl;
}

function start_task($auth, $id){
    $curl = curl($auth, '[{"operationName":"GetTaskById","variables":{"taskId":"'.$id.'"},"query":"fragment FragmentCampaignTask on CampaignTaskOutput {\\n  id\\n  name\\n  description\\n  status\\n  type\\n  position\\n  buttonText\\n  coinsRewardAmount\\n  spinEnergyRewardAmount\\n  link\\n  userTaskId\\n  isRequired\\n  iconUrl\\n  taskVerificationType\\n  verificationAvailableAt\\n  shouldUseVpn\\n  isLinkInternal\\n  quiz {\\n    id\\n    question\\n    answers\\n    __typename\\n  }\\n  __typename\\n}\\n\\nquery GetTaskById($taskId: String!) {\\n  campaignTaskGetConfig(taskId: $taskId) {\\n    ...FragmentCampaignTask\\n    __typename\\n  }\\n}"},{"operationName":"TwitterProfile","variables":{},"query":"query TwitterProfile {\\n  twitterProfile {\\n    username\\n    __typename\\n  }\\n}"}]')[0]['data']['campaignTaskGetConfig']['status'];
    return $curl;
}

function verify_task($auth, $id){
    $curl = curl($auth, '[{"operationName":"CampaignTaskToVerification","variables":{"taskConfigId":"'.$id.'"},"query":"fragment FragmentCampaignTask on CampaignTaskOutput {\\n  id\\n  name\\n  description\\n  status\\n  type\\n  position\\n  buttonText\\n  coinsRewardAmount\\n  spinEnergyRewardAmount\\n  link\\n  userTaskId\\n  isRequired\\n  iconUrl\\n  taskVerificationType\\n  verificationAvailableAt\\n  shouldUseVpn\\n  isLinkInternal\\n  quiz {\\n    id\\n    question\\n    answers\\n    __typename\\n  }\\n  __typename\\n}\\n\\nmutation CampaignTaskToVerification($taskConfigId: String!) {\\n  campaignTaskMoveToVerificationV2(taskConfigId: $taskConfigId) {\\n    ...FragmentCampaignTask\\n    __typename\\n  }\\n}"}]')[0]['data']['campaignTaskMoveToVerificationV2']['status'];
    return $curl;
}

function checkpoint($auth, $id){
    $curl = curl($auth, '[{"operationName":"GetTaskById","variables":{"taskId":"'.$id.'"},"query":"fragment FragmentCampaignTask on CampaignTaskOutput {\\n  id\\n  name\\n  description\\n  status\\n  type\\n  position\\n  buttonText\\n  coinsRewardAmount\\n  spinEnergyRewardAmount\\n  link\\n  userTaskId\\n  isRequired\\n  iconUrl\\n  taskVerificationType\\n  verificationAvailableAt\\n  shouldUseVpn\\n  isLinkInternal\\n  quiz {\\n    id\\n    question\\n    answers\\n    __typename\\n  }\\n  __typename\\n}\\n\\nquery GetTaskById($taskId: String!) {\\n  campaignTaskGetConfig(taskId: $taskId) {\\n    ...FragmentCampaignTask\\n    __typename\\n  }\\n}"}]');
    $time = $curl[0]['data']['campaignTaskGetConfig']['verificationAvailableAt'];
    $target = strtotime($time);
    $current = time();
    $solve_time = ($target - $current) + 3;
    return array($curl[0]['data']['campaignTaskGetConfig']['userTaskId'], $solve_time);
}

function solve_task($auth, $id){
    $curl = curl($auth, '[{"operationName":"CampaignTaskMarkAsCompleted","variables":{"userTaskId":"'.$id.'"},"query":"fragment FragmentCampaignTask on CampaignTaskOutput {\\n  id\\n  name\\n  description\\n  status\\n  type\\n  position\\n  buttonText\\n  coinsRewardAmount\\n  spinEnergyRewardAmount\\n  link\\n  userTaskId\\n  isRequired\\n  iconUrl\\n  taskVerificationType\\n  verificationAvailableAt\\n  shouldUseVpn\\n  isLinkInternal\\n  quiz {\\n    id\\n    question\\n    answers\\n    __typename\\n  }\\n  __typename\\n}\\n\\nmutation CampaignTaskMarkAsCompleted($userTaskId: String!, $verificationCode: String, $quizAnswers: [CampaignTaskQuizQuestionInput!]) {\\n  campaignTaskMarkAsCompleted(\\n    userTaskId: $userTaskId\\n    verificationCode: $verificationCode\\n    quizAnswers: $quizAnswers\\n  ) {\\n    ...FragmentCampaignTask\\n    __typename\\n  }\\n}"}]')[0]['data']['campaignTaskMarkAsCompleted']['status'];
    return $curl;
}

function curl($auth = false, $body){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api-gw-tg.memefi.club/graphql');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $headers = array();
    $headers[] = 'Accept: */*';
    $headers[] = 'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7,ms;q=0.6';
    if($auth){
        $headers[] = 'Authorization: Bearer '.$auth;
    }
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Origin: https://tg-app.memefi.club';
    $headers[] = 'Referer: https://tg-app.memefi.club/';
    $headers[] = 'User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Mobile Safari/537.36';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    $decode = json_decode($result, true);
    return $decode;
}