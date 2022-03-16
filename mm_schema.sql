# an ensemble (performing group)
# Info is kept in ensemble/ID.json
#
create table ensemble (
    id              integer     not null auto_increment,
    create_time     double      not null,
    user_id         integer     not null,
    name            varchar(255)    not null default '',
    unique(name),
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
    req_msg         varchar(255)    not null default '',
    reply_msg       varchar(255)    not null default '',
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

alter table notify
    add column sent_by_email double not null default 0,
    add column last_view double not null default 0,
    add column id2 integer not null default 0
;

# a search and its results
#
create table search (
    id              integer     not null auto_increment,
    create_time     double      not null,
    user_id         integer     not null,
    desc            text        not null,
        # role, search params, and results in JSON
    retry_time      double      not null default 0,
        # when we last retried the search
    nnew            integer     not null default 0,
        # number of new results found on retry
    index(user_id),
    primary key (id)
) engine = InnoDB;
