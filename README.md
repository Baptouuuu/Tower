# Tower

This is a command line tool to deploy your code base with a new approach. Instead of building another tool sending a set of shell commands over ssh with a logic of point to point, Tower takes the approach of servers as nodes of a tree where from a node you trigger the *tower* of sub-nodes.

The setup:

* one install of Tower per server
* the config to deploy the server is localised on it
* a *node* only knows its childs' servers

Advantages:

* One only knows what can be deployed on a server (not how)
* Keep the *know how to deploy* on the server
* If the command set is updated, no one is impacted
* Esaily cascade deployment (if a node retrieve sources from its parent)
* Nodes at the same level can be deployed in parrallel (via background jobs, loose direct output)
* Commands run locally, everything's logged so you can trace what/when an env is deployed

Drawbacks:

* A node need to know how to connect to its childs (if node is hacked the subtree is compromised)
* A node need to know how to connect to its parent to retrieve sources (lower the load on master repository)
* Cross relation between parent and childs

Example:
```
              A (you)
             / \
            /   \
           /     \
   (prod) B       C (staging)
         / \       \
        /   \       \
       /     \       \
      D       E       F
      |       |
      |       |
      G       H
```

Say here the tree below `B` is also for production, with this tool you could easily place the same Tower setup on the five servers and to deploy all of them, you would run a command like `twr deploy B --cascade` and done!
Or if you want to only deploy `H`, just to be sure everythings deploying fine, connect to the machine and locally run `twr deploy:env prod` or add this as one of the child on your local machine (`A`) and run `twr deploy H`. Once again it's a tree, you can start from wherever you want.

But in a normal case you would just hace `B` and `C` as childs of your machine.

To be really efficient (meaning not overloading your VCS server, especially if deploying asynchronously), setup childs to retrieve code from their parent. For instance, setup `B` and `C` with your VCS server as `git remote`; `D` and `E` have `B` as `git remote`, and so on...

*Note*: I talk about git here, but your not forced to use it (but cool kids do ;))

*Note*: When cascading, if a child fail to deploy, it's subtree won't be deployed

Another use case would be you have a single server for your app, and other servers for related services required by your first server. You could imply, with this notion of tree, that every time you deploy your app it triggers the deployment of those related services (so everything is always up-to-date).

## Installation

### Cloning

Clone this repository to the machine you want as a node
```sh
git clone git@github.com:Baptouuuu/Tower.git /wherever/you/want
```

Install composer
```sh
curl -sS https://getcomposer.org/installer | php ; ./composer.phar install
```

Create your `config.yml` file
```sh
cp config/config.yml.dist config/config.yml
```

(Optional) For easier access, make the command available as a global one
```sh
ln -sn /tower/folder/twr /usr/bin/twr
```

### Phar file

You can also download the [phar file](twr.phar) and create a file named `tower.yml` in your app (look at the [dist file](config/config.yml.dist) for an example).

And to use it:
```sh
./twr.phar deploy
```

**Note**: you can run `chmod + x twr.phar && mv twr.phar /usr/bin/tower`, so you can run the tower deploy from any project.

Done!

## Configuration

```yaml
log_path: %root_dir%/deploy.log # path where you want to get your log (%root_dir% is the path to the tower directory)

macros:
    macroName: # set of commands (can be nested)
        - mysqldump
        - git clone git@host:repo.git releases/xxxx-xx-xx

envs:
    prod: # define where is a local environment + how to deploy it
        path: /var/www/my-awesome-project
        commands:
            - %macroName%
            - curl -sS https://getcomposer.org/installer | php ; ./composer.phar install
        rollback: # this is optional (useful to cleanup if the deployment fails)
            - rm -rf release/xxxx-xx-xx
    # you can define multiple environments

childs:
    subNodeName:
        host: hostname.tld
        path: /path/to/tower/folder # path for the `subNodeName`server
    # you can define multiple childs
```

*Notes*:

* rotate your logs, every output for a local deployment is logged (so it can quickly become huge)
* a macro can contain another macro, still the same syntax: `%macroName%`
* setup ssh keys between a node and its childs to connect without password, otherwise cascade deployment will fail

## Usage

Deploy an env when your on the machine:
```sh
twr deploy:env envName [... envNameX] [--continue]
```
The `continue` flag is useful when you deploy multiple environments, if one fails it will continue to deploy the others (this flag is off by default, and always off when deploying in cascade)

Deploy a child
```sh
twr deploy child [... childX] [--env=env1] [--env=envX] [--async] [-c|--cascade]
```
This would run the command `twr deploy:env env1 envX` on the machine called `child`.

`async` flag run the previous command in background and displays you the resulting PID, and then move to the next child specified to deploy.

`cascade` flag tells the child to deploy its childs when itself is deployed succesfully.

**Important**: when cascading, all childs and subchilds of the node you specified will be deployed (so be sure how to architect your tree).
