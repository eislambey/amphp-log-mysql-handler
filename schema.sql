create table logs
(
    id       int auto_increment primary key,
    channel  varchar(64)                  not null,
    message  text                         null,
    context  longtext collate utf8mb4_bin null,
    level    smallint                     null,
    datetime datetime                     not null,
    constraint context
        check (json_valid(`context`))
);
