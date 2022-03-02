# extra fields for user table
#
alter table user
    add

# an ensemble (performing group)
# Info is kept in ensemble/ID.json
#
create table ensemble (
    id              integer     not null auto_increment,
    create_time     double      not null,
    user_id         integer     not null,
    primary key (id)
) engine = InnoDB;

create table ensemble_member (
    create_time     double      not null,
    ensemble_id     integer     not null,
    user_id         integer     not null,
    status          tinyint     not null,
        # 0 pending approval
        # 1 approved (user is member)
        # 2 declined
        # 3 removed
    req_msg         varchar(255)    not null,
    reply_msg       varchar(255)    not null,
    unique(ensemble_id, user_id)
) engine = InnoDB;

create table notification (
    id              integer     not null auto_increment,
    create_time     double      not null,
    user_id         integer     not null,
    sent_by_email   double      not null default 0,
    last_view       double      not null default 0,
    type            tinyint     not null,
    id1             integer     not null default 0,
    id2             integer     not null default 0,
    index(user_id),
    primary key (id)
) engine = InnoDB;

# a search and its results
#
create table search (
    id              integer     not null auto_increment,
    create_time     double      not null,
    user_id         integer     not null,
    params          text        not null,
        # search params in JSON
    results         text        not null,
        # results (list of user IDs) in JSON
    index(user_id),
    primary key (id)
) engine = InnoDB;
