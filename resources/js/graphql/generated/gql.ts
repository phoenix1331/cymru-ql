/* eslint-disable */
import * as types from './graphql';
import type { TypedDocumentNode as DocumentNode } from '@graphql-typed-document-node/core';

/**
 * Map of all GraphQL operations in the project.
 *
 * This map has several performance disadvantages:
 * 1. It is not tree-shakeable, so it will include all operations in the project.
 * 2. It is not minifiable, so the string of a GraphQL query will be multiple times inside the bundle.
 * 3. It does not support dead code elimination, so it will add unused operations.
 *
 * Therefore it is highly recommended to use the babel or swc plugin for production.
 * Learn more about it here: https://the-guild.dev/graphql/codegen/plugins/presets/preset-client#reducing-bundle-size
 */
type Documents = {
    "\n    query Leagues {\n        leagues {\n            slug\n            name\n            tier\n            region\n        }\n    }\n": typeof types.LeaguesDocument,
    "\n    query ClubPage($slug: String!) {\n        club(slug: $slug) {\n            slug\n            name\n            nickname\n            colour\n            founded\n            ground {\n                slug\n                name\n                town\n                capacity\n                opened\n            }\n            seasons {\n                position\n                season {\n                    slug\n                    name\n                }\n                league {\n                    slug\n                    name\n                    tier\n                }\n            }\n            league {\n                league {\n                    slug\n                    name\n                }\n                season {\n                    name\n                }\n                table {\n                    ...TableRowFields\n                }\n            }\n            awayTrips {\n                roadKm\n                to {\n                    slug\n                    name\n                    town\n                    clubs {\n                        slug\n                        name\n                    }\n                }\n            }\n        }\n    }\n\n    fragment TableRowFields on TableRow {\n        position\n        played\n        won\n        drawn\n        lost\n        goalDifference\n        points\n        form\n        club {\n            slug\n            name\n            colour\n        }\n    }\n": typeof types.ClubPageDocument,
};
const documents: Documents = {
    "\n    query Leagues {\n        leagues {\n            slug\n            name\n            tier\n            region\n        }\n    }\n": types.LeaguesDocument,
    "\n    query ClubPage($slug: String!) {\n        club(slug: $slug) {\n            slug\n            name\n            nickname\n            colour\n            founded\n            ground {\n                slug\n                name\n                town\n                capacity\n                opened\n            }\n            seasons {\n                position\n                season {\n                    slug\n                    name\n                }\n                league {\n                    slug\n                    name\n                    tier\n                }\n            }\n            league {\n                league {\n                    slug\n                    name\n                }\n                season {\n                    name\n                }\n                table {\n                    ...TableRowFields\n                }\n            }\n            awayTrips {\n                roadKm\n                to {\n                    slug\n                    name\n                    town\n                    clubs {\n                        slug\n                        name\n                    }\n                }\n            }\n        }\n    }\n\n    fragment TableRowFields on TableRow {\n        position\n        played\n        won\n        drawn\n        lost\n        goalDifference\n        points\n        form\n        club {\n            slug\n            name\n            colour\n        }\n    }\n": types.ClubPageDocument,
};

/**
 * The graphql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 *
 *
 * @example
 * ```ts
 * const query = graphql(`query GetUser($id: ID!) { user(id: $id) { name } }`);
 * ```
 *
 * The query argument is unknown!
 * Please regenerate the types.
 */
export function graphql(source: string): unknown;

/**
 * The graphql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function graphql(source: "\n    query Leagues {\n        leagues {\n            slug\n            name\n            tier\n            region\n        }\n    }\n"): (typeof documents)["\n    query Leagues {\n        leagues {\n            slug\n            name\n            tier\n            region\n        }\n    }\n"];
/**
 * The graphql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function graphql(source: "\n    query ClubPage($slug: String!) {\n        club(slug: $slug) {\n            slug\n            name\n            nickname\n            colour\n            founded\n            ground {\n                slug\n                name\n                town\n                capacity\n                opened\n            }\n            seasons {\n                position\n                season {\n                    slug\n                    name\n                }\n                league {\n                    slug\n                    name\n                    tier\n                }\n            }\n            league {\n                league {\n                    slug\n                    name\n                }\n                season {\n                    name\n                }\n                table {\n                    ...TableRowFields\n                }\n            }\n            awayTrips {\n                roadKm\n                to {\n                    slug\n                    name\n                    town\n                    clubs {\n                        slug\n                        name\n                    }\n                }\n            }\n        }\n    }\n\n    fragment TableRowFields on TableRow {\n        position\n        played\n        won\n        drawn\n        lost\n        goalDifference\n        points\n        form\n        club {\n            slug\n            name\n            colour\n        }\n    }\n"): (typeof documents)["\n    query ClubPage($slug: String!) {\n        club(slug: $slug) {\n            slug\n            name\n            nickname\n            colour\n            founded\n            ground {\n                slug\n                name\n                town\n                capacity\n                opened\n            }\n            seasons {\n                position\n                season {\n                    slug\n                    name\n                }\n                league {\n                    slug\n                    name\n                    tier\n                }\n            }\n            league {\n                league {\n                    slug\n                    name\n                }\n                season {\n                    name\n                }\n                table {\n                    ...TableRowFields\n                }\n            }\n            awayTrips {\n                roadKm\n                to {\n                    slug\n                    name\n                    town\n                    clubs {\n                        slug\n                        name\n                    }\n                }\n            }\n        }\n    }\n\n    fragment TableRowFields on TableRow {\n        position\n        played\n        won\n        drawn\n        lost\n        goalDifference\n        points\n        form\n        club {\n            slug\n            name\n            colour\n        }\n    }\n"];

export function graphql(source: string) {
  return (documents as any)[source] ?? {};
}

export type DocumentType<TDocumentNode extends DocumentNode<any, any>> = TDocumentNode extends DocumentNode<  infer TType,  any>  ? TType  : never;