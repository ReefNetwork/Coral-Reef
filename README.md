# Coral Reef

# How to use

### Config

```yaml
mysqlHost: localhost #Mysqlのアドレス
mysqlPassword: password #Mysqlのパスワード
discordToken: token #DiscordBotのトーケン(結構長い)
chatChannelId: 000000000000000000 #チャットの相互通信をするチャンネルのid
logChannelId: 111111111111111111 #ログを送信するチャンネルのid
```

### Mysql

データベース`CoralReef`と`CoralReefLog`の事前作成  
データベース`CoralReef`と`CoralReefLog`に権限を持っているユーザー`pmmp`の作成

```mysql
CREATE DATABASE CoralReef;
CREATE DATABASE CoralReefLog;
CREATE USER pmmp IDENTIFIED BY 'password';
GRANT ALL on CoralReef.* to pmmp;
GRANT ALL on CoralReefLog.* to pmmp;
```

# Composer

```
composer install
```
