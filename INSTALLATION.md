# Installation
This document dives a little bit deeper into installing your component on a kubernetes cluster, looking for information on setting up your component on a local machine? Take a look at the [tutorial](TUTORIAL.md) instead. 

For installation of components you will have to have [helm 3](https://helm.sh) and [kubectl](https://kubernetes.io/docs/tasks/tools/install-kubectl/) installed.

## Minimal system requirements for your cluster
- Kubernetes 1.16 of later
- A minimum 3 nodes
- 4 vCPUs per node
- 4 GB RAM per node
- 50 GB disk space per node

## Kubernetes Providers
There is a number of Kubernetes providers that are suitable to run CommonGround components. Most notable are:

- [Fuga cloud](https://fuga.cloud)
- [TransIP](https://transip.nl)
- [Digital Ocean](https://digitalocean.com)
- [Google Cloud](https://cloud.google.com)
- [Amazon Web Services](https://aws.amazon.com)

For which we have to note that although the last three providers have servers within The Netherlands, because the Safe Harbour ruling has been invalidated by the European court of Justice and the American ownership of these servers are not suitable for production clusters.

## Deploying trough commonground.nu
We strongly advise you to use [CommonGround.nu](https://commonground.nu) for the deployment of your components. An extensive tutorial can be found [here]().

If you do not wish to use CommonGround.nu, you will have to follow the following tutorial. Otherwise, we strongly encourage you to read the chapter about [helm settings](INSTALLATION.md#Helm-settings)

## Setting up helm repositories
We will first show you how to add the ingress nginx repository of helm and kubernetes to your helm repositories. We do this using the following command:
```CLI
$ helm repo list
```

If in the output there is no repository 'ingress-nginx' we need to add it:

```CLI
$ helm repo add stable https://kubernetes.github.io/ingress-nginx
```

Congratulations! You added your first repository to helm.

## Setting up ingress
We need at least one nginx controller per kubernetes kluster, doh optionally we could set on up on a per namebase basis

```CLI
$ helm install ingress-nginx/ingress-nginx --name loadbalancer --kubeconfig kubeconfig.yaml
```

After installing a component we can check that out with 

```CLI
$ kubectl describe ingress pc-dev-ingress -n=kube-system --kubeconfig kubeconfig.yaml
```

## Setting up Kubernetes Dashboard
After we installed helm we can easily use both to install kubernetes dashboard

```CLI
$ kubectl create -f https://raw.githubusercontent.com/kubernetes/dashboard/v2.0.0/aio/deploy/recommended.yaml --kubeconfig kubeconfig.yaml
```

This should return the token, copy it to somewhere save (just the token not the other returned information) and start up a dashboard connection

```CLI
$ kubectl proxy --kubeconfig kubeconfig.yaml
```

This should proxy our dashboard to helm making it available trough our favorite browser and a simple link
```CLI
http://localhost:8001/api/v1/namespaces/kube-system/services/https:dashboard-kubernetes-dashboard:https/proxy/#!/login
```

Then, you can login using the Kubeconfig option and uploading your kubeconfig.

## Deploying trough helm
First we always need to update our dependencies
```CLI
$ helm dependency update ./api/helm
```

Then we need to set up the desired namespaces
```CLI
$ kubectl create namespace dev
$ kubectl create namespace stag
$ kubectl create namespace prod
```

If you want to create a new instance
```CLI
$ helm install pc-dev ./api/helm  --kubeconfig kubeconfig.yaml --namespace dev  --set settings.env=dev,settings.debug=1
$ helm install pc-stag ./api/helm --kubeconfig kubeconfig.yaml --namespace stag --set settings.env=stag,settings.debug=0,settings.cache=1
$ helm install pc-prod ./api/helm --kubeconfig kubeconfig.yaml --namespace prod --set settings.env=prod,settings.debug=0,settings.cache=1
```
This will create an instance by the name of pc-dev (line 1) pc-stag (line 2) or pc-prod (line 3) on your cluster, with the environment, debug and cache settings configured (see [helm settings](INSTALLATION.md#helm-settings) for more information). 

Or update if you want to update an existing one
```CLI
$ helm upgrade pc-dev ./api/helm  --kubeconfig kubeconfig.yaml --namespace dev  --set settings.env=dev,settings.debug=1
$ helm upgrade pc-stag ./api/helm --kubeconfig kubeconfig.yaml --namespace stag --set settings.env=stag,settings.debug=0,settings.cache=1
$ helm upgrade pc-prod ./api/helm --kubeconfig kubeconfig.yaml --namespace prod --set settings.env=prod,settings.debug=0,settings.cache=1
```

Or just restart the containers of the component
```CLI
$ kubectl rollout restart deployments/pc-php --namespace dev --kubeconfig kubeconfig.yaml
$ kubectl rollout restart deployments/pc-nginx --namespace dev --kubeconfig kubeconfig.yaml
$ kubectl rollout restart deployments/pc-varnish --namespace dev --kubeconfig kubeconfig.yaml
``` 

Or del if you want to delete an existing one
```CLI
$ helm del pc-dev --kubeconfig kubeconfig.yaml
$ helm del pc-stag --kubeconfig kubeconfig.yaml
$ helm del pc-prod --kubeconfig kubeconfig.yaml
```

Note that you can replace common ground with the namespace that you want to use (normally the name of your component).

## Helm settings
When installing components there is a number of settings that can be edited to modify the working of your component. The most important of these settings are:

- ```settings.env```: This setting influences primarily the container to be used. There are three regular possibilities: ```dev```, ```stag``` and ```prod```. 
   - ```dev``` will load the latest new container, which can be unstable because this is the version that is developed on.
   - ```stag``` will load the latest semi-stable version of the container, this setting is recommended for acceptation environments
   - ```prod``` will load the ```latest``` images, which are the latest stable version. This setting is recommended for production environments 
- ```settings.debug```: This setting can enable the extensive debugging tools included in Symfony. This is recommended in development environments by setting it to 1. However, debugging takes a lot of power from your cluster, so we recommend to switch it off in production or acceptation environments (by setting it to 0)
- ```settings.cache```: This setting can enable caching in your component. This means that traffic can be prevented by checking if a resource has already been requested and if it is still in cache. However, this means also that a version of a resource can be loaded that has been changed on the source. Therefore we recommend to switch this off in development environments (by setting this option to 0) and enable (by setting this option to 1) it on production environments to enhance the response times of the component.
- ```settings.web```: This setting determines if the component has to be exposed to the outside world. Setting it to 0 will not expose your component outside of the cluster (recommended), switching it to 1 will expose your component to ingress (recommended for front-end applications).

## Making your app known on NLX
The proto component comes with an default NLX setup, if you made your own component however you might want to provide it trough the [NLX](https://www.nlx.io/) service. Fortunately the proto component comes with an nice setup for NLX integration.

First of all change the necessary lines in the [.env](.env) file, basically everything under the NLX setup tag. Keep in mind that you wil need to have your component available on an (sub)domain name (a simple IP wont sufice).

To force the re-generation of certificates simply delete the org.crt en org.key in the api/nlx-setup folder.


## Setting up analytics and a help chat function
As a developer you might be interested to know how your application documentation is used, so you can see which parts of your documentation are most read and which parts might need some additional love. You can measure this (and other user interactions) with google tag manager. Just add your google tag id to the .env file (replacing the default) under GOOGLE_TAG_MANAGER_ID. This will only enable Google analytics on your documentation page, it will never analyse the actual traffic of the API.

Have you seen our sweet support-chat on the documentation page? We didn't build that ourselves ;). We use a Hubspot chat for that, just head over to Hubspot, create an account and enter your Hubspot embed code in het .env file (replacing the default) under HUBSPOT_EMBED_CODE.

Would you like to use a different analytics or chat-tool? Just shoot us a [feature request](https://github.com/ConductionNL/commonground-component/issues/new?assignees=&labels=&template=feature_request.md&title=New%20Analytics%20or%20Chat%20provider)!  
