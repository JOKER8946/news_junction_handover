<?
include '../inc/validate.logged.php';
include '../inc/config.php';
include 'genai_function.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == "reset") {
            unset($_SESSION['prompt_message']);
            echo "Ok";
        }
    } else {
        $type = 'genai_creator';
        $profession = fetchPrompt($db, $gUserId);
        if (isset($_POST['working_headline'])) {
            $working_headline = $_POST['working_headline'];
        } else {
            $working_headline = '';
        }
        if (isset($_POST['avatar'])) {
            $avatar = $_POST['avatar'];
        }
        $avatarData = fetchAvatar($avatar);

        $model = "gpt-4o";
        $system_prompt = array(
            array(
                "role" => "system",
                "content" => "Try to complete the sentence within the max_tokens."
            ),
            array(
                "role" => "system",
                "content" => "Try to complete the sentence even if the max_tokens is reached."
            ),
            array(
                "role" => "system",
                "content" => "Always use mysql compatible svg embeds to show icons or images if you are using them to pepper the response."
            ),
            array(
                "role" => "system",
                "content" => "Do not use any svgs"
            )
        );
        if ($profession != null) {
            $system_prompt[] = array(
                "role" => "system",
                "content" => $profession
            );
        }
        if ($avatarData != null) {
            $system_prompt[] = array(
                "role" => "system",
                "content" => $avatarData
            );
        }

        $user_prompts = isset($_SESSION['prompt_message']) ? $_SESSION['prompt_message'] : array();

        $user_prompts[] = array(
            "role" => "user",
            "content" => $working_headline
        );

        print_r(processPrompt($type, $system_prompt, $user_prompts, $model));
    }
}
