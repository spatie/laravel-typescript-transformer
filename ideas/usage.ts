import { ResourceController } from './ResourceController';
import {InvokableController} from "./InvokableController";

function sendResourceRequest(data: ResourceController.update.Request): ResourceController.update.Response{
    console.log(data);

    return data;
}

sendResourceRequest({ id: 5 });

const resourceUrl = ResourceController.update({team: 2});

function sendInvokableRequest(data: InvokableController.Request): InvokableController.Response{
    console.log(data);

    return data;
}

sendInvokableRequest({ id: 5 });

const invokableUrl = InvokableController({});

