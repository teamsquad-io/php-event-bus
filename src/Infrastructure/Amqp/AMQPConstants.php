<?php

namespace TeamSquad\EventBus\Infrastructure\Amqp;

class AMQPConstants
{
    const PRECONDITION_CODE = -5;

    const TEST_PING_QUEUE = "test.queue.ping";

    const LITESPEED_EXCHANGE = "litespeed.exchange";
    const LITESPEED_QUEUE = "litespeed.queue.flushCache";
    const ROUTING_FLUSH_CACHE = "flushCache";

    const ERRORLOGGER_EXCHANGE = "errorLogger.exchange";
    const ERRORLOGGER_QUEUE = "errorLogger.queue.log";
    const ERRORLOGGER_LOG_ROUTING = "log";

    const ASYNC_LOGS_EXCHANGE = "logs";

    const LOGS_EXCHANGE = "logs.exchange";
    const LOGS_QUEUE = "logs.queue.log";
    const LOGS_ROUTING = "log";

    const WEB_SERVER_EXCHANGE = "webserver.exchange";
    const WEB_SERVER_FLUSH_CACHE_QUEUE = "webserver.queue.flushCache";
    const ROUTING_FLUSH_LOCALHOST_CACHE = "flushLocalhostCache";

    const SHOWS_EXCHANGE = "shows.exchange";
    const SHOWS_UPDATE_EXCHANGE = "shows.exchange.updateStatus";
    const SHOWS_CHAT_LAST_SEEN_QUEUE = "shows.queue.updateChatLastSeen";
    const SHOWS_VIDEO_LAST_SEEN_QUEUE = "shows.queue.updateVideoLastSeen";
    const SHOWS_UPDATE_STATUS_QUEUE = "shows.queue.updateStatus";
    const SHOWS_CHAT_LAST_SEEN_ROUTING = "updateChatLastSeen";
    const SHOWS_VIDEO_LAST_SEEN_ROUTING = "updateVideoLastSeen";
    const SHOWS_UPDATE_STATUS_ROUTING = "updateStatus";

    const WOWZASERVER_EXCHANGE = "wowza";

    const WOWZA_EXCHANGE = "wowza.exchange";
    const WOWZA_SWITCH_EXCHANGE = "wowza.switch.exchange";
    const WOWZA_EVENTS_QUEUE = "wowza.queue.wowzaEvents";
    const WOWZA_EVENTS_EMITTER_LIST_QUEUE = "wowza.queue.wowzaEventsEmitterList";
    const MONIT_SHOWS_WOWZA_QUEUE = "wowza.queue.monit";

    const FFMPEG_EXCHANGE = "ffmpeg.exchange";
    const FFMPEG_BAN_ROUTING = "ban";
    const FFMPEG_BAN_QUEUE = "ffmpeg.queue.ban";

    const FFMPEG_UNBAN_ROUTING = "unban";
    const FFMPEG_UNBAN_QUEUE = "ffmpeg.queue.unban";


    const ROUTING_WOWZA_EVENTS = "wowzaEvents";
    const ROUTING_WOWZA_EVENTS_EMITTER_LIST = "wowzaEventsEmitterList";

    const NOTIFICATIONS_EXCHANGE = "chat.notifications.exchange";
    const NOTIFICATIONS_ROUTING = "notification";

    const CHAT_SYSTEM_EXCHANGE = "chat.system.exchange";
    const CHAT_SYSTEM_ROUTING = "system";

    const CHAT_USERS_EXCHANGE = "chat.user.exchange";
    const CHAT_USERS_QUEUE = "chat.user.queue";
    const CHAT_USERS_ROUTING = "";


    const EMAIL_EXCHANGE = "email.exchange";
    const EMAIL_EXCHANGE_DLX = "emailDlx.exchange";

    const SYSTEM_EMAIL_QUEUE = "email.queue.systemEmail";
    const MASSIVE_EMAIL_QUEUE = "email.queue.masiveEmail";
    const PRIORITY_EMAIL_QUEUE = "email.queue.priorityEmail";

    const SYSTEM_EMAIL_QUEUE_DLX = "emailDlx.queue.systemEmail";
    const MASSIVE_EMAIL_QUEUE_DLX = "emailDlx.queue.masiveEmail";
    const PRIORITY_EMAIL_QUEUE_DLX = "emailDlx.queue.priorityEmail";

    const ROUTING_MASSIVE_EMAIL = "masiveEmail";
    const ROUTING_SYSTEM_EMAIL = "systemEmail";
    const ROUTING_PRIORITY_EMAIL = "priorityEmail";

    const MAILING_QUEUE = "mailing.queue";
    const MAILING_QUEUE_DLX = "mailing.queue.dlx";

    const TIMEOUT_EXCHANGE = "timeout.exchange";
    const TIMEOUT_QUEUE = "timeout.queue";

    const WOWZA_START_PUBLISH_QUEUE = "wowza.queue.streamStarted";
    const WOWZA_STOP_PUBLISH_QUEUE = "wowza.queue.streamStopped";
    const WOWZA_EMITTERINFO_QUEUE = "wowza.queue.emmiterInfo";
    const WOWZA_EMMITER_COUNT_QUEUE = "wowza.queue.emmiter_count";
    const WOWZA_VIEWER_COUNT_QUEUE = "wowza.queue.viewer_count";
    const WOWZA_KICK_EMITTER_QUEUE = "wowza.queue.kickedEmitter";

    const SYSTEM_EXCHANGE = "system.exchange";
    const SYSTEM_DL_EXCHANGE = "systemDL.exchange";

    const MAINTENANCE_DL_QUEUE = "systemDL.queue.maintenance";
    const MAINTENANCE_QUEUE = "system.queue.maintenance";
    const ROUTING_START_MAINTENANCE = "startMaintenance";

    const ANTIFRAUD_EXCHANGE = "antifraud.exchange";
    const ANTIFRAUD_AUTODONATION_QUEUE = "antifraud.queue.autoDonation";
    const ROUTING_AUTODONATION = "autoDonation";

    const ROUTING_UNMUTE_VIDEO_FOR_VIEWER = "unmuteVideoForViewer";
    const ROUTING_MUTE_VIDEO = "muteShowVideoButNotUsersInGame";
    const ROUTING_UNMUTE_VIDEO_FOR_EVERYBODY = "unmuteShowVideoForEverybody";

    const PRIVATE_EXCHANGE = "private.exchange";

    const MONIT_EXCHANGE = "monit.signals";
    const MONIT_DL_EXCHANGE = "monitDL.exchange";
    const MONIT_SENDSIGNAL = "monit.queue.sendsignal";
    const MONIT_RETRIEVESIGNAL = "monit.queue.retrievesignal";
    const MONIT_WAITFORACTION_DL_QUEUE = "monit.queue.waitForActionDL";

    const ROUTING_SENDSIGNAL = "send.signal";
    const ROUTING_RETRIEVESIGNAL = "retrieve.signal";


    const RPC_EXCHANGE = "rpc.exchange";
    const RPC_CHANGE_COLOR_QUEUE = "rpc.queue.changeColor";
    const RPC_CHANGE_CHAT_SOUND_QUEUE = "rpc.queue.changeChatSound";
    const RPC_UPDATE_MAX_USERS_QUEUE = "rpc.queue.updateMaxUsers";
    const RPC_DISCONNECT_TV_QUEUE = "rpc.queue.disconnectTV";
    const RPC_UPDATE_CHANNEL_USERS_COUNT_QUEUE = "rpc.queue.updateChannelUsersCount";
    const RPC_MUTE_TV_FOR_VIEWER_QUEUE = "rpc.queue.muteTVForViewer";
    const RPC_GET_USER_INFO_QUEUE = "rpc.queue.getUserInfo";
    const RPC_CREATE_CHANNEL_QUEUE = "rpc.queue.createChannel";
    const RPC_REPORT_CAM_QUEUE = "rpc.queue.reportCam";
    const RPC_SAVE_TOPIC_QUEUE = "rpc.queue.saveTopic";
    const RPC_DISCONNECT_VIEWER_FROM_TV_QUEUE = "rpc.queue.disconnectViewerFromTV";
    const RPC_CLOSE_CHANNEL_QUEUE = "rpc.queue.closeChannel";
    const RPC_GLOBAL_BAN_QUEUE = "rpc.queue.globalBan";
    const RPC_CAM_BAN_QUEUE = "rpc.queue.camBan";
    const RPC_REMOVE_CAM_BAN_QUEUE = "rpc.queue.removeCamBan";
    const RPC_GROUP_BAN_PETITION_QUEUE = "rpc.queue.groupBanPetition";
    const RPC_GET_CONFIG_FROM_DB = "rpc.queue.getConfigFromDb";


    const ROUTING_GET_CONFIG_FROM_DB = "getConfigFromDb";
    const ROUTING_CHANGE_COLOR_RPC = "changeColor";
    const ROUTING_CHANGE_CHAT_SOUND_RPC = "changeChatSound";
    const ROUTING_UPDATE_MAX_USERS_RPC = "updateMaxUsers";
    const ROUTING_DISCONNECT_TV_RPC = "disconnectTV";
    const ROUTING_UPDATE_CHANNEL_USERS_COUNT = "updateChannelUsersCount";
    const ROUTING_PRIVATE_CHAT_FINISH_RPC = "privateChatFinish";
    const ROUTING_PRIVATE_SHOW_RPC = 'canPrivateShowRequest';
    const ROUTING_PRIVATE_SHOW_UPDATE_AVERAGE_RPC = 'privateShowUpdateAverage';


    const ROUTING_MUTE_TV_FOR_PRIVATE_RPC = "muteTVForPrivate";
    const ROUTING_MUTE_TV_FOR_VIEWER_RPC = "muteTVForViewer";
    const ROUTING_UNMUTE_TV_RPC = "unmuteTV";
    const ROUTING_PRIVATE_CHAT_ADMIN_START_RPC = "privateChatAdminStart";
    const ROUTING_GET_USER_INFO_RPC = "getUserInfo";
    const ROUTING_CREATE_CHANNEL_RPC = "createChannel";
    const ROUTING_REPORT_CAM_RPC = "reportCam";
    const ROUTING_PRIVATE_CHAT_CHECK_RPC = "privateChatCheck";
    const ROUTING_PRIVATE_CHAT_REQUEST_RPC = "privateChatRequest";
    const ROUTING_PRIVATE_CHAT_START_RPC = "privateChatStart";
    const ROUTING_DISCONNECT_VIEWER_FROM_TV_RPC = "disconnectViewerFromTV";
    const ROUTING_CLOSE_CHANNEL_RPC = "closeChannel";
    const ROUTING_GLOBAL_BAN_RPC = "globalBan";


    const TRANSMITTERDATA_EXCHANGE = "transmitterData.exchange";
    const CONTEST_TABLE_QUEUE = "transmitterData.queue.contestTable";
    const BIND_KEY_CONTEST_TABLE = "contestTable";
    const ROUTING_CONTEST_TABLE = "contestTable";

    const RETRY_TRANSMITTERDATA_EXCHANGE = "retryTransmitterData.exchange";
    const RETRY_CONTEST_TABLE_QUEUE = "retryTransmitterData.queue.contestTable";


    const ROUTING_CAM_BAN_RPC = "camBan";
    const ROUTING_REMOVE_CAM_BAN_RPC = "removeCamBan";
    const ROUTING_GROUP_BAN_PETITION_RPC = "groupBanPetition";

    const PAYMENT_PROVIDERS_EXCHANGE = "paymentProviders.exchange";
    const PROCESS_CARD_PROVIDER_CALLBACK_QUEUE = "paymentProviders.queue.processCardCallback";

    const VALIDATE_BILLING_PROVIDER_CALLBACK_QUEUE = "paymentProviders.queue.validateBillingCallback";
    const VALIDATE_BILLING_PROVIDER_CALLBACK_ROUTING = "validateBillingCallback";

    const PROCESS_BILLING_PROVIDER_CALLBACK_QUEUE = "paymentProviders.queue.processBillingCallback";
    const PROCESS_BILLING_PROVIDER_CALLBACK_ROUTING = "processBillingCallback";

    const ALERTS_QUEUE = "alerts.queue.sendAlert";
    const ALERTS_EXCHANGE = "alerts";
    const ALERTS_ROUTING = "send_alert";

    /**
     * ACTIVATED USER CHAT LOGGED IN
     */

    const USER_ACTIVATED_LOGGED_IN_EXCHANGE = 'chat.user_activated.exchange';
    const USER_ACTIVATED_LOGGED_IN_QUEUE = 'chat.user_activated.queue';

    /**
     * PAYMENTS CONSUMER
     */

    const PAYMENTS_EXCHANGE = "payments.exchange";

    const GENERATE_USER_PAYMENT_QUEUE = "payments.queue.generateUserPayment";
    const GENERATE_USER_PAYMENT_ROUTING = "generateUserPayment";

    const FORCE_USER_PAYMENT_QUEUE = "payments.queue.forceUserPayment";
    const FORCE_USER_PAYMENT_ROUTING = "forceUserPayment";

    const MARK_PAYED_USER_PAYMENT_QUEUE = "payments.queue.markUserPaymentAsPayed";
    const MARK_PAYED_USER_PAYMENT_ROUTING = "markUserPaymentAsPayed";

    const GENERATE_COMMUNITY_PAYMENT_QUEUE = "payments.queue.generateCommunityPayment";
    const GENERATE_COMMUNITY_PAYMENT_ROUTING = "generateCommunityPayment";

    const MARK_PAYED_COMMUNITY_PAYMENT_QUEUE = "payments.queue.markCommunityPaymentAsPayed";
    const MARK_PAYED_COMMUNITY_PAYMENT_ROUTING = "markCommunityPaymentAsPayed";


    /**
     * EARNINGS CONSUMER
     */

    const EARNINGS_EXCHANGE = "earnings.exchange";

    const CALCULATE_USER_EARNING_QUEUE = "earnings.queue.calculateUserEarning";
    const CALCULATE_USER_EARNING_ROUTING = "calculateUserEarning";

    const CALCULATE_COMMUNITY_EARNING_QUEUE = "earnings.queue.calculateCommunityEarning";
    const CALCULATE_COMMUNITY_EARNING_ROUTING = "calculateCommunityEarning";

    const CALCULATE_ALL_COMMUNITY_EARNINGS_AND_STATS_QUEUE = "earnings.queue.calculateAllCommunityEarningsAndStats";
    const CALCULATE_ALL_COMMUNITY_EARNINGS_AND_STATS_ROUTING = "calculateAllCommunityEarningsAndStats";

    const EARNINGS_TRANSFER_COINS_QUEUE = "earnings.queue.transferCoins";
    const EARNINGS_TRANSFER_COINS_ROUTING = "transferCoins";

    const GIVE_TEMP_PREMIUM_QUEUE = "earnings.queue.giveTempPremium";
    const GIVE_TEMP_PREMIUM_ROUTING = "giveTempPremium";
    const GIVE_CONTEST_DONATION_QUEUE = "earnings.queue.giveContestDonation";
    const GIVE_CONTEST_DONATION_ROUTING = "giveContestDonation";

    const GIVE_OFFLINE_DONATION_QUEUE = "earnings.queue.giveOfflineDonation";
    const GIVE_OFFLINE_DONATION_ROUTING = "giveOfflineDonation";

    const CALCULATE_CHARGEBACK_EARNINGS_QUEUE = "earnings.queue.calculateChargeBackEarnings";
    const CALCULATE_CHARGEBACK_EARNINGS_ROUTING = "calculateChargeBackEarnings";


    /**
     * ADMINS EXCHANGE
     */
    const ADMINS_EXCHANGE = "admins";
    const ADMIN_REQUESTED_USER_ANONYMIZATION = "userAnonymizationRequested";
    const USER_ANONYMIZATION_QUEUE = "admins.queue.userAnonymizationRequested";

    /**
     * FAVORITES CONSUMER
     */

    const FAVORITES_EXCHANGE = "favorites";

    const USER_FOLLOWING_CAM_QUEUE = "favorites.queue.userFollowingCam";
    const USER_FOLLOWING_CAM_ROUTING = "userFollowingCam";

    const CAM_UPLOADS_NEW_VIDEO_QUEUE = "favorites.queue.camUploadsNewVideo";
    const CAM_UPLOADS_NEW_VIDEO_ROUTING = "camUploadsNewVideo";

    /**
     * GAME CONSUMER
     */
    const GAME_CREATED_EXCHANGE = 'games.created.exchange';
    const GAME_ACCEPTED_EXCHANGE = 'games.accepted.exchange';
    const GAME_COMPLETED_EXCHANGE = 'games.completed.exchange';
    const GAME_REJECTED_EXCHANGE = 'games.rejected.exchange';
    const GAME_MUTED_EXCHANGE = 'games.muted.exchange';
    const GAME_UNMUTED_EXCHANGE = 'games.unmuted.exchange';
    const GAME_TOPIC_UPDATED_EXCHANGE = 'games.topicupdated.exchange';

    const TOPIC_GAME_FINISHED_QUEUE = 'topic.games.queue.finished';
    const TOPIC_UPDATED_QUEUE = 'topic.games.queue.updated';

    const CAMLIST_GAME_MUTED_QUEUE = 'camlist.games.queue.muted';
    const CAMLIST_GAME_UNMUTED_QUEUE = 'camlist.games.queue.unmted';
    const FOOTBALL_EXCHANGE = 'football.exchange';


    /**
     * USER
     */
    const USER_TIPS_EXCHANGE = 'user.tip_sent.exchange';
    const USER_RATED_CAM_EXCHANGE = 'user.cam_rated.exchange';
    const EMAIL_VALIDATED_EXCHANGE = 'user.email_validated.exchange';

    /** USER ACTIONS */
    const EMAIL_VALIDATED = 'email_validated';
    const TIP_SENT = 'tip_sent';
    const CAM_RATED = 'cam_rated';
    const CHECK_CAM_RATING = 'check_my_cam_rating';

    /** USER ACTIONS CONSEQUENCES */
    const GIVE_GIFT_QUEUE = 'give.gift.queue';
    const GOAL_SCORED_QUEUE = 'football.queue.goal.scored';
    const GOAL_SCORED = 'football.goal.scored';
    const CHAT_MESSAGES_GOAL_SCORED_QUEUE = 'chat.messages.goal.scored.queue';
    const COOK_SHOW_GIVEN_COINS_QUEUE = 'show.given.coins.cook.queue';
    const COOK_CAM_RATINGS_QUEUE = 'cam.rating.cook.queue';

    /** VTS EXCHANGE */
    const VTS_EXCHANGE = "vts.eventBus";
    const VTS_COMMAND_EXCHANGE = "vts.commandBus";
    const VTS_EXCHANGE_TYPE= "topic";
    const VTS_COMMAND_EXCHANGE_TYPE= "topic";

    const SPEND_COINS_QUEUE = "vts.wallet.registerPositiveTransaction";
    const RECHARGE_COINS_QUEUE = "vts.wallet.registerNegativeTransaction";
    const PAID_COIN_MOVEMENTS_QUEUE = "vts.coinMovements.registerPositiveMovement";
    const RECHARGE_PAID_COIN_MOVEMENTS_QUEUE = "vts.coinMovements.registerNegativeMovement";
    const LOGGING_EVENTBUS = "vts.eventBus.logging";
    const REWARD_GIVEN_QUEUE = "vts.event.listen.RewardGiven.PaidCoinMovementsConsumer";
    const GAME_REJECTED_QUEUE = "vts.event.listen.GameRejected.PaidCoinMovementsConsumer";
}
